<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Performance indexes for the query paths that actually run hot.
 *
 * Deliberately lean. Every index below is justified by a real query in this repo
 * on a table that grows over time; tables that stay small (testimonials, faqs,
 * events, teams, careers, case_studies, custom_codes) get nothing, because on a
 * few dozen rows InnoDB table-scans faster than it index-dives and the index
 * would be pure write overhead. See docs/DATABASE.md.
 *
 * Additive only — no drops of columns or tables here. Destructive cleanup lives
 * in the next migration so it can be reviewed and run separately.
 *
 * Index existence is checked against information_schema so this is safe to run
 * against client databases in unknown states.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Hot path: Media::imagePayloadMap() resolves featured images with
        // whereIn('path', …) on every blog index, archive and post page.
        // Prefix index — Blueprint has no syntax for it, and 191 matches the
        // utf8mb4 convention used throughout this schema.
        $this->addRawIndex('media', 'media_path_index', 'path(191)');

        // Hot path: Post::scopePublished() filters status + published_at, then
        // every caller orders by published_at. Trailing id is the pagination
        // tiebreaker — borrowed from WordPress's type_status_date index — so
        // `ORDER BY published_at DESC, id DESC` stays index-served on ties.
        $this->addIndex('posts', 'posts_status_published_at_id_index', ['status', 'published_at', 'id']);

        // activity_log grows without bound. This serves both the admin audit
        // listing (->latest(), currently a filesort over the whole table) and
        // the pruning DELETE that should be deleting by age.
        $this->addIndex('activity_log', 'activity_log_created_at_index', ['created_at']);

        // Enquiry inbox: ->latest() with an optional whereNull/whereNotNull
        // read_at filter. One composite serves the filter and the sort.
        $this->addIndex('enquiries', 'enquiries_read_at_created_at_index', ['read_at', 'created_at']);

        // Media library listing is ->latest() with paginate(24). Media is the
        // one "small" table that reliably grows on an image-heavy client site.
        $this->addIndex('media', 'media_created_at_index', ['created_at']);

        // Serves the not_found_logs prune added in routes/console.php, which
        // deletes by last_seen_at. Without this the weekly sweep table-scans
        // exactly the table it exists to keep small.
        $this->addIndex('not_found_logs', 'not_found_logs_last_seen_at_index', ['last_seen_at']);

        // --- Redundant index removal -------------------------------------
        // enquiries.email is only ever queried as LIKE '%term%' (leading
        // wildcard), which no B-tree can serve. The index has never been usable
        // and costs a write on every enquiry insert.
        $this->dropIndexIfExists('enquiries', 'enquiries_email_index');

        // enquiries.read_at standalone is now a left-prefix of the composite
        // added above, so MySQL can serve every query the old index served.
        $this->dropIndexIfExists('enquiries', 'enquiries_read_at_index');
    }

    public function down(): void
    {
        $this->addIndex('enquiries', 'enquiries_read_at_index', ['read_at']);
        $this->addIndex('enquiries', 'enquiries_email_index', ['email']);

        $this->dropIndexIfExists('not_found_logs', 'not_found_logs_last_seen_at_index');
        $this->dropIndexIfExists('media', 'media_created_at_index');
        $this->dropIndexIfExists('enquiries', 'enquiries_read_at_created_at_index');
        $this->dropIndexIfExists('activity_log', 'activity_log_created_at_index');
        $this->dropIndexIfExists('posts', 'posts_status_published_at_id_index');
        $this->dropIndexIfExists('media', 'media_path_index');
    }

    /**
     * Create an index from plain column names, skipping if the table or the
     * index is absent (modules may not be installed on every client site).
     */
    private function addIndex(string $table, string $name, array $columns): void
    {
        if (! Schema::hasTable($table) || $this->indexExists($table, $name)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        $cols = implode(', ', array_map(fn ($c) => "`{$c}`", $columns));
        DB::statement("CREATE INDEX `{$name}` ON `{$table}` ({$cols})");
    }

    /**
     * Create an index from a raw column expression (prefix indexes, functional
     * indexes) that Blueprint cannot express.
     */
    private function addRawIndex(string $table, string $name, string $expression): void
    {
        if (! Schema::hasTable($table) || $this->indexExists($table, $name)) {
            return;
        }

        DB::statement("CREATE INDEX `{$name}` ON `{$table}` ({$expression})");
    }

    private function dropIndexIfExists(string $table, string $name): void
    {
        if (! Schema::hasTable($table) || ! $this->indexExists($table, $name)) {
            return;
        }

        DB::statement("DROP INDEX `{$name}` ON `{$table}`");
    }

    private function indexExists(string $table, string $name): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $name)
            ->exists();
    }
};

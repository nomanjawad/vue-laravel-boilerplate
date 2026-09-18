<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Remove schema left behind by the September 2026 repositioning.
 *
 * DESTRUCTIVE — kept separate from the additive index migration so it can be
 * reviewed and run on its own. Both objects below are verified dead in this
 * codebase, but a client site that was migrated from an older version may still
 * hold data in them, so posts.category_id is checked for values before it goes.
 *
 *   - `pages`            The Page model was deleted; pages now live in
 *                        data/pages/{slug}.json. No reference to Page:: or the
 *                        table remains anywhere in app/.
 *   - posts.category_id  Superseded by the category_post pivot (added in
 *                        2026_09_04_230000). No model relation, controller or
 *                        Vue page reads it, but MySQL still maintains its index
 *                        and validates the FK on every write to posts.
 *
 * See docs/DATABASE.md, finding #6.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('pages');

        if (! Schema::hasTable('posts') || ! Schema::hasColumn('posts', 'category_id')) {
            return;
        }

        // Refuse to silently discard data. If a client site still has values
        // here, surface it rather than dropping — they need migrating into
        // category_post first.
        $orphaned = DB::table('posts')->whereNotNull('category_id')->count();

        if ($orphaned > 0) {
            throw new RuntimeException(
                "Refusing to drop posts.category_id: {$orphaned} row(s) still hold a value. "
                .'Migrate them into the category_post pivot first, then re-run this migration.'
            );
        }

        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('posts') && ! Schema::hasColumn('posts', 'category_id')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->foreignId('category_id')->nullable()->after('user_id')
                    ->constrained()->nullOnDelete();
            });
        }

        if (! Schema::hasTable('pages')) {
            Schema::create('pages', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->longText('body')->nullable();
                $table->string('template', 50)->default('default');
                $table->boolean('is_published')->default(false);
                $table->integer('sort_order')->default(0);
                $table->string('meta_title')->nullable();
                $table->string('meta_description', 500)->nullable();
                $table->string('og_image')->nullable();
                $table->timestamps();
            });
        }
    }
};

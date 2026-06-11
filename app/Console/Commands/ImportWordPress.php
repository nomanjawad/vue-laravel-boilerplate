<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Services\MediaService;
use App\Services\SlugService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Import a WordPress WXR export (Tools > Export in wp-admin) into the template.
 *
 * - posts/pages with their categories and publish status
 * - attachments are downloaded through MediaService, so every image follows the
 *   single storage convention (WebP on the public disk) instead of leaving a
 *   mixed uploads/ vs storage/ mess behind
 * - wp-content/uploads URLs inside post bodies are rewritten to the new paths
 */
class ImportWordPress extends Command
{
    protected $signature = 'import:wordpress
        {file : Path to the WXR export XML file}
        {--dry-run : Parse and report without writing anything}
        {--skip-media : Import content but leave image URLs untouched}';

    protected $description = 'Import posts, pages, categories and media from a WordPress WXR export';

    /** Map of original attachment URL => new local URL, used to rewrite bodies. */
    private array $mediaMap = [];

    public function handle(MediaService $media, SlugService $slugs): int
    {
        $file = $this->argument('file');

        if (! is_readable($file)) {
            $this->error("Cannot read file: {$file}");

            return self::FAILURE;
        }

        $xml = simplexml_load_file($file, options: LIBXML_NOCDATA);

        if ($xml === false || ! isset($xml->channel)) {
            $this->error('Not a valid WordPress WXR export file.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $items = $xml->channel->item;
        $userId = User::orderBy('id')->value('id');

        $stats = ['categories' => 0, 'posts' => 0, 'pages' => 0, 'media' => 0, 'skipped' => 0];

        // Pass 1: categories (channel-level definitions).
        foreach ($xml->channel->children('wp', true)->category ?? [] as $cat) {
            $name = (string) $cat->children('wp', true)->cat_name;
            $slug = (string) $cat->children('wp', true)->category_nicename;

            if ($name === '') {
                continue;
            }

            $stats['categories']++;

            if (! $dryRun) {
                Category::firstOrCreate(['slug' => $slug ?: Str::slug($name)], ['name' => $name]);
            }
        }

        // Pass 2: attachments first, so post bodies can be rewritten in pass 3.
        if (! $this->option('skip-media')) {
            foreach ($items as $item) {
                if ((string) $item->children('wp', true)->post_type !== 'attachment') {
                    continue;
                }

                $url = (string) $item->children('wp', true)->attachment_url;

                if (! $url || ! preg_match('/\.(jpe?g|png|gif|webp|svg)$/i', $url)) {
                    continue;
                }

                $stats['media']++;

                if ($dryRun) {
                    continue;
                }

                try {
                    $response = Http::timeout(30)->get($url);

                    if ($response->successful()) {
                        $imported = $media->importFromContents($response->body(), basename($url), $userId);
                        $this->mediaMap[$url] = '/storage/'.$imported->path;
                    }
                } catch (\Throwable $e) {
                    $this->warn("  media failed: {$url} ({$e->getMessage()})");
                    $stats['media']--;
                    $stats['skipped']++;
                }
            }
        }

        // Pass 3: posts and pages.
        foreach ($items as $item) {
            $wp = $item->children('wp', true);
            $type = (string) $wp->post_type;

            if (! in_array($type, ['post', 'page'], true)) {
                continue;
            }

            $title = trim((string) $item->title) ?: 'Untitled';
            $body = $this->rewriteUploadUrls((string) $item->children('content', true)->encoded);
            $status = (string) $wp->status === 'publish' ? 'published' : 'draft';
            $publishedAt = (string) $wp->post_date_gmt;
            $slugSource = (string) $wp->post_name ?: $title;

            if ($type === 'post') {
                $stats['posts']++;

                if ($dryRun) {
                    continue;
                }

                $categoryId = null;
                foreach ($item->category ?? [] as $cat) {
                    if ((string) $cat['domain'] === 'category') {
                        $categoryId = Category::where('slug', (string) $cat['nicename'])->value('id');
                        break;
                    }
                }

                $post = new Post;
                $post->fill([
                    'user_id' => $userId,
                    'category_id' => $categoryId,
                    'title' => $title,
                    'slug' => $slugs->generate($post, $slugSource),
                    'excerpt' => trim((string) $item->children('excerpt', true)->encoded) ?: null,
                    'body' => $body,
                    'status' => $status,
                    'published_at' => $status === 'published' && $publishedAt && $publishedAt !== '0000-00-00 00:00:00' ? $publishedAt : null,
                ])->save();
            } else {
                $stats['pages']++;

                if ($dryRun) {
                    continue;
                }

                $page = new Page;
                $page->fill([
                    'title' => $title,
                    'slug' => $slugs->generate($page, $slugSource),
                    'body' => $body,
                    'is_published' => $status === 'published',
                ])->save();
            }
        }

        $mode = $dryRun ? ' (dry run — nothing written)' : '';
        $this->info("Import complete{$mode}:");
        $this->table(['Categories', 'Posts', 'Pages', 'Media', 'Skipped'], [array_values($stats)]);

        if (! $dryRun) {
            $this->comment('Review imported drafts in the admin, then clear caches (Dashboard > Clear Cache).');
        }

        return self::SUCCESS;
    }

    /** Rewrite wp-content/uploads URLs in body HTML to the imported local paths. */
    private function rewriteUploadUrls(string $body): string
    {
        foreach ($this->mediaMap as $old => $new) {
            $body = str_replace($old, $new, $body);

            // Also catch resized variants WP generates (image-300x200.jpg).
            $pattern = preg_quote(preg_replace('/\.(\w+)$/', '', $old), '/').'-\d+x\d+\.'.pathinfo($old, PATHINFO_EXTENSION);
            $body = preg_replace('/'.$pattern.'/', $new, $body);
        }

        return $body;
    }
}

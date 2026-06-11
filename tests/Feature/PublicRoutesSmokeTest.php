<?php

namespace Tests\Feature;

use App\Models\Career;
use App\Models\CaseStudy;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\Redirect;
use App\Models\Setting;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Hits every public GET route and asserts it does not 500.
 *
 * This is the boilerplate's safety net: it catches broken route bindings,
 * bad column names, cache-serialization bugs, and missing route files —
 * the exact bug classes that have shipped to production before.
 */
class PublicRoutesSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(); // roles, admin user, menus, settings, page metas

        $user = User::first(['*']) ?? User::factory()->create();
        $category = Category::create(['name' => 'Smoke', 'slug' => 'smoke']);

        Post::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Smoke Post',
            'slug' => 'smoke-post',
            'body' => 'body',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        Product::create([
            'name' => 'Smoke Product',
            'slug' => 'smoke-product',
            'price' => 9.99,
            'stock_quantity' => 5,
            'is_active' => true,
            'category_id' => $category->id,
        ]);

        Page::create([
            'title' => 'Smoke Page',
            'slug' => 'smoke-page',
            'body' => 'body',
            'is_published' => true,
        ]);

        Career::create([
            'title' => 'Smoke Career',
            'slug' => 'smoke-career',
            'type' => 'full-time',
            'description' => 'desc',
            'is_active' => true,
        ]);

        CaseStudy::create([
            'title' => 'Smoke Case Study',
            'slug' => 'smoke-case-study',
            'body' => 'body',
            'is_active' => true,
        ]);

        Team::create([
            'name' => 'Smoke Member',
            'position' => 'Tester',
            'is_active' => true,
        ]);
    }

    /**
     * Slug/param substitutions for routes with URL parameters.
     * Add an entry here when introducing a new parameterized public route.
     */
    private const PARAMS = [
        'post' => 'smoke-post',
        'product' => 'smoke-product',
        'page' => 'smoke-page',
        'career' => 'smoke-career',
        'caseStudy' => 'smoke-case-study',
        'case_study' => 'smoke-case-study',
        'category' => 'smoke',
        'slug' => 'smoke-page',
        'token' => 'dummy-token',
    ];

    public function test_all_public_get_routes_do_not_500(): void
    {
        $failures = [];

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $uri = $route->uri();

            // Skip non-page endpoints and the admin panel (covered by auth redirects below).
            if (preg_match('#^(_|sanctum|storage|up$)#', $uri)) {
                continue;
            }

            // Substitute route parameters with seeded slugs.
            $resolved = preg_replace_callback('/\{(\w+?)(:[^}]+)?\??\}/', function ($m) {
                return self::PARAMS[$m[1]] ?? 'smoke-missing';
            }, $uri);

            $response = $this->get('/'.ltrim($resolved, '/'));

            if ($response->status() >= 500) {
                $failures[] = sprintf('%s -> %d', $resolved, $response->status());
            }
        }

        $this->assertSame([], $failures, "Routes returned 5xx:\n".implode("\n", $failures));
    }

    public function test_admin_routes_redirect_guests(): void
    {
        $this->get('/admin')->assertRedirect();
    }

    public function test_home_renders_inertia(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_active_redirects_are_followed(): void
    {
        Redirect::create([
            'from_path' => '/old-blog-url',
            'to_path' => '/blog',
            'status_code' => 301,
        ]);

        $this->get('/old-blog-url')->assertRedirect('/blog')->assertStatus(301);
    }

    public function test_missed_urls_are_logged_for_the_redirect_manager(): void
    {
        $this->get('/definitely-not-a-page')->assertNotFound();

        $this->assertDatabaseHas('not_found_logs', ['path' => '/definitely-not-a-page']);
    }

    public function test_newsletter_signup_stores_subscriber(): void
    {
        $this->post('/newsletter', ['email' => 'reader@example.com'])->assertRedirect();

        $this->assertDatabaseHas('subscribers', ['email' => 'reader@example.com']);
    }

    public function test_sitemap_includes_published_content(): void
    {
        config(['template.indexable' => true]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee('/blog/smoke-post');
    }

    public function test_sitemap_hidden_when_not_indexable(): void
    {
        config(['template.indexable' => false]);

        $this->get('/sitemap.xml')->assertNotFound();
    }

    public function test_settings_shared_with_frontend_are_whitelisted(): void
    {
        Setting::create([
            'key' => 'recaptcha_secret_key',
            'value' => 'super-secret',
            'type' => 'string',
            'group' => 'integrations',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('super-secret');
    }

    public function test_changing_a_published_post_slug_creates_a_redirect(): void
    {
        $admin = User::role('admin')->first();
        $post = Post::where('slug', '=', 'smoke-post', 'and')->first();

        $this->actingAs($admin)->put("/admin/posts/{$post->id}", [
            'title' => $post->title,
            'slug' => 'smoke-post-renamed',
            'body' => $post->body,
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('redirects', [
            'from_path' => '/blog/smoke-post',
            'to_path' => '/blog/smoke-post-renamed',
        ]);
        $this->get('/blog/smoke-post')->assertRedirect('/blog/smoke-post-renamed');
    }
}

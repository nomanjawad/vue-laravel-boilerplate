<?php

namespace Tests\Feature;

use App\Models\Career;
use App\Models\CaseStudy;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Page;
use App\Models\PageMeta;
use App\Models\Post;
use App\Models\Product;
use App\Models\Redirect;
use App\Models\Subscriber;
use App\Models\Tag;
use App\Models\Team;
use App\Models\User;
use App\Modules\Core\ModuleManager;
use App\Modules\Core\PermissionSyncer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Mirror of PublicRoutesSmokeTest for the admin panel: super-admin hits every
 * GET route asserting non-5xx, then editor/guest assertions confirm the RBAC
 * matrix actually bites and module disable/unhealthy states are isolated.
 */
class AdminRoutesSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        // Make sure roles + permissions are in sync after seed().
        app(PermissionSyncer::class)->sync();

        $superRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole($superRole);

        $this->editor = User::factory()->create();
        $this->editor->assignRole('editor');

        $user = $this->superAdmin;
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

        Page::create(['title' => 'Smoke Page', 'slug' => 'smoke-page', 'body' => 'body', 'is_published' => true]);
        Career::create(['title' => 'Smoke Career', 'slug' => 'smoke-career', 'type' => 'full-time', 'description' => 'desc', 'is_active' => true]);
        CaseStudy::create(['title' => 'Smoke CS', 'slug' => 'smoke-cs', 'body' => 'body', 'is_active' => true]);
        Team::create(['name' => 'Smoke Team', 'position' => 'Tester', 'is_active' => true]);
        Tag::create(['name' => 'Smoke Tag', 'slug' => 'smoke-tag']);
        Menu::create(['title' => 'Smoke Menu', 'url' => '/', 'location' => 'header', 'sort_order' => 0, 'is_active' => true]);
        PageMeta::firstOrCreate(['route_name' => 'home'], ['title' => 'Home', 'description' => 'Home']);
        Redirect::create(['from_path' => '/old', 'to_path' => '/new', 'status_code' => 301]);
        Subscriber::create(['email' => 'a@example.com']);
    }

    public function test_super_admin_can_visit_every_admin_get_route(): void
    {
        $failures = [];

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $uri = '/'.ltrim($route->uri(), '/');
            if (! str_starts_with($uri, '/admin')) {
                continue;
            }
            if (preg_match('#\{[^}]+\?\}#', $uri)) {
                // Optional params skipped — only required-param routes are tested.
            }

            // Substitute params with seeded ids/slugs.
            $resolved = preg_replace_callback('/\{(\w+?)(:[^}]+)?\??\}/', function ($m) {
                return match ($m[1]) {
                    'post', 'category', 'product', 'tag', 'career', 'caseStudy', 'case_study', 'team' => '1',
                    'menu', 'pageMeta', 'redirect', 'subscriber', 'media', 'user' => '1',
                    'order' => '1',
                    'key' => 'blog',
                    default => '1',
                };
            }, $uri);

            $response = $this->actingAs($this->superAdmin)->get($resolved);
            $status = $response->getStatusCode();
            if ($status >= 500) {
                $failures[] = sprintf('%s -> %d', $resolved, $status);
            }
        }

        $this->assertSame([], $failures, "Admin routes returned 5xx:\n".implode("\n", $failures));
    }

    public function test_editor_cannot_reach_settings(): void
    {
        $this->actingAs($this->editor)->get('/admin/settings')->assertForbidden();
    }

    public function test_editor_cannot_reach_modules(): void
    {
        $this->actingAs($this->editor)->get('/admin/modules')->assertForbidden();
    }

    public function test_guest_admin_redirects_to_login(): void
    {
        $this->get('/admin')->assertRedirect();
    }

    public function test_disabled_module_routes_return_404(): void
    {
        // Blog routes are gated by the module middleware via the can: matrix.
        // Disabling blog should make /admin/posts inaccessible.
        $manager = app(ModuleManager::class);
        $manager->disable('blog');

        $this->actingAs($this->superAdmin)->get('/admin/posts')->assertStatus(404);

        $manager->enable('blog');
        $this->actingAs($this->superAdmin)->get('/admin/posts')->assertOk();
    }

    public function test_unhealthy_module_does_not_break_the_panel(): void
    {
        $manager = app(ModuleManager::class);
        $manager->markUnhealthy('blog', new \RuntimeException('boom'));

        // Dashboard must still render with one module marked sick.
        $this->actingAs($this->superAdmin)->get('/admin')->assertOk();
    }
}

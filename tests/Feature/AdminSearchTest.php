<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use App\Modules\Core\ModuleManager;
use App\Modules\Core\PermissionSyncer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminSearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        app(PermissionSyncer::class)->sync();

        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']));

        $category = Category::create(['name' => 'Smoke', 'slug' => 'smoke']);

        Post::create([
            'user_id' => $this->admin->id,
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
    }

    public function test_search_returns_matching_posts_and_products(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson('/admin/search?q=smoke')
            ->assertOk();

        $groups = collect($response->json('groups'));
        $labels = $groups->pluck('label')->all();

        $this->assertContains('Posts', $labels);
        $this->assertContains('Products', $labels);

        $postTitles = collect($groups->firstWhere('label', 'Posts')['results'] ?? [])
            ->pluck('title')
            ->all();
        $this->assertContains('Smoke Post', $postTitles);
    }

    public function test_search_requires_at_least_two_characters(): void
    {
        $this->actingAs($this->admin)
            ->getJson('/admin/search?q=s')
            ->assertOk()
            ->assertJson(['groups' => []]);
    }

    public function test_search_skips_disabled_modules(): void
    {
        app(ModuleManager::class)->disable('blog');

        $groups = $this->actingAs($this->admin)
            ->getJson('/admin/search?q=smoke')
            ->assertOk()
            ->json('groups');

        $labels = collect($groups)->pluck('label')->all();
        $this->assertNotContains('Posts', $labels);
        $this->assertContains('Products', $labels);

        app(ModuleManager::class)->enable('blog');
    }

    public function test_super_admin_sees_all_enabled_module_groups(): void
    {
        $super = User::factory()->create();
        $super->assignRole(Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']));

        $labels = collect(
            $this->actingAs($super)
                ->getJson('/admin/search?q=smoke')
                ->assertOk()
                ->json('groups')
        )->pluck('label')->all();

        $this->assertContains('Posts', $labels);
        $this->assertContains('Products', $labels);
    }

    public function test_guest_cannot_search(): void
    {
        $this->getJson('/admin/search?q=smoke')->assertUnauthorized();
    }
}

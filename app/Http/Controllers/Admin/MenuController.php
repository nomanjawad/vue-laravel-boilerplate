<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Post;
use App\Services\JsonDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MenuController extends Controller
{
    public function __construct(private JsonDataService $jsonData) {}

    public function index()
    {
        $locations = config('template.menu_locations', [
            'header' => 'Header',
            'footer' => 'Footer',
        ]);

        $trees = [];
        foreach (array_keys($locations) as $location) {
            $trees[$location] = $this->treeFor($location);
        }

        $pages = collect($this->jsonData->list('pages'))->map(function (string $slug) {
            $data = $this->jsonData->get("pages/{$slug}");

            return [
                'slug' => $slug,
                'title' => is_string($data['title'] ?? null) && $data['title'] !== ''
                    ? $data['title']
                    : $slug,
                'url' => $slug === 'home' ? '/' : '/'.$slug,
            ];
        })->values()->all();

        $posts = Post::query()
            ->published()
            ->orderByDesc('published_at')
            ->limit(100)
            ->get(['id', 'title', 'slug'])
            ->map(fn (Post $post) => [
                'id' => $post->id,
                'title' => $post->title,
                'url' => '/blog/'.$post->slug,
            ])
            ->all();

        return Inertia::render('Admin/Menus/Index', [
            'locations' => $locations,
            // Named `trees` — shared Inertia prop `menus` is the public nav
            // tree (MenusData). Reusing that key overwrote / collided and made
            // the admin builder receive Proxies that structuredClone rejected.
            'trees' => $trees,
            'pages' => $pages,
            'posts' => $posts,
        ]);
    }

    public function store(Request $request)
    {
        $locationKeys = array_keys(config('template.menu_locations', [
            'header' => 'Header',
            'footer' => 'Footer',
        ]));

        // sort_order is nullable: the admin form binds a number input via
        // Vue's `.number` modifier, which emits an empty string (not 0 or
        // null) once the field is cleared — a bare `integer` rule 422s that
        // silently, with no error shown. See feedback.md §34.
        $validated = $request->validate([
            'location' => ['required', Rule::in($locationKeys)],
            'title' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:menus,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $validated['url'] = Menu::normalizeUrl($validated['url']);
        $validated['sort_order'] ??= 0;
        $validated['is_active'] = $validated['is_active'] ?? true;

        if (! empty($validated['parent_id'])) {
            $parent = Menu::findOrFail($validated['parent_id']);
            abort_unless($parent->location === $validated['location'], 422, 'Parent must be in the same location.');
            abort_unless($parent->parent_id === null, 422, 'Menu nesting is limited to 2 levels.');
        } else {
            $validated['parent_id'] = null;
        }

        Menu::create($validated);

        return back()->with('success', 'Menu item created successfully.');
    }

    public function update(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'url' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:menus,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $validated['url'] = Menu::normalizeUrl($validated['url']);
        // A cleared order field means "leave it as-is", not "reset to null".
        if (! array_key_exists('sort_order', $validated) || $validated['sort_order'] === null) {
            unset($validated['sort_order']);
        }

        if (array_key_exists('parent_id', $validated)) {
            if ($validated['parent_id'] === null || $validated['parent_id'] === '') {
                $validated['parent_id'] = null;
            } else {
                abort_if((int) $validated['parent_id'] === $menu->id, 422, 'A menu item cannot be its own parent.');
                $parent = Menu::findOrFail($validated['parent_id']);
                abort_unless($parent->location === $menu->location, 422, 'Parent must be in the same location.');
                abort_unless($parent->parent_id === null, 422, 'Menu nesting is limited to 2 levels.');
                // Don't allow nesting an item that already has children (would create depth 3).
                abort_if($menu->children()->exists(), 422, 'Move or remove children before nesting this item.');
            }
        }

        $menu->update($validated);

        return back()->with('success', 'Menu item updated successfully.');
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:menus,id'],
            'items.*.parent_id' => ['nullable', 'integer', 'exists:menus,id'],
            'items.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $ids = collect($validated['items'])->pluck('id')->all();
        $menus = Menu::whereIn('id', $ids)->get()->keyBy('id');

        DB::transaction(function () use ($validated, $menus) {
            // Two-pass: clear parents first so unique/FK edge cases don't
            // trip when swapping parent/child in one batch.
            foreach ($validated['items'] as $item) {
                Menu::where('id', $item['id'])->update(['parent_id' => null]);
            }

            foreach ($validated['items'] as $item) {
                $menu = $menus->get($item['id']);
                if (! $menu) {
                    continue;
                }

                $parentId = $item['parent_id'] ?? null;
                if ($parentId !== null) {
                    $parent = $menus->get($parentId) ?? Menu::find($parentId);
                    abort_unless($parent && $parent->location === $menu->location, 422);
                    // Depth cap: parent must be a root in the *final* tree.
                    // After pass 1 every parent_id is null; check the payload.
                    $parentPayload = collect($validated['items'])->firstWhere('id', $parentId);
                    abort_if(
                        $parentPayload && ($parentPayload['parent_id'] ?? null) !== null,
                        422,
                        'Menu nesting is limited to 2 levels.'
                    );
                    abort_if((int) $parentId === (int) $menu->id, 422);
                }

                Menu::where('id', $item['id'])->update([
                    'parent_id' => $parentId,
                    'sort_order' => $item['sort_order'],
                ]);
            }
        });

        return back()->with('success', 'Menu order saved.');
    }

    public function destroy(Menu $menu)
    {
        $menu->delete();

        return back()->with('success', 'Menu item deleted successfully.');
    }

    /**
     * @return list<array{id: int, title: string, url: string, parent_id: int|null, sort_order: int, is_active: bool, children: list<array>}>
     */
    private function treeFor(string $location): array
    {
        $roots = Menu::where('location', $location)
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return $roots->map(fn (Menu $menu) => $this->toAdminNode($menu))->values()->all();
    }

    /** @return array{id: int, title: string, url: string, parent_id: int|null, sort_order: int, is_active: bool, children: list<array>} */
    private function toAdminNode(Menu $menu): array
    {
        return [
            'id' => $menu->id,
            'title' => $menu->title,
            'url' => $menu->url,
            'parent_id' => $menu->parent_id,
            'sort_order' => (int) $menu->sort_order,
            'is_active' => (bool) $menu->is_active,
            // Depth cap 2: children never expose their own children.
            'children' => $menu->children->map(fn (Menu $child) => [
                'id' => $child->id,
                'title' => $child->title,
                'url' => $child->url,
                'parent_id' => $child->parent_id,
                'sort_order' => (int) $child->sort_order,
                'is_active' => (bool) $child->is_active,
                'children' => [],
            ])->values()->all(),
        ];
    }
}

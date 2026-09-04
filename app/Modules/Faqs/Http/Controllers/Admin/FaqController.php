<?php

namespace App\Modules\Faqs\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Faqs\Http\Requests\StoreFaqRequest;
use App\Modules\Faqs\Http\Requests\UpdateFaqRequest;
use App\Modules\Faqs\Models\Faq;
use App\Services\JsonDataService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    public function __construct(private JsonDataService $jsonData) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Faq::class);

        $pageFilter = $request->input('page_slug');

        return Inertia::render('Faqs/Admin/Faqs/Index', [
            'faqs' => Faq::query()
                ->when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
                ->when($pageFilter === '__global__', fn ($q) => $q->whereNull('page_slug'))
                ->when(
                    is_string($pageFilter) && $pageFilter !== '' && $pageFilter !== '__global__',
                    fn ($q) => $q->where('page_slug', $pageFilter)
                )
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'filters' => [
                'search' => $request->input('search'),
                'page_slug' => $pageFilter,
            ],
            'pages' => $this->pageOptions(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Faq::class);

        return Inertia::render('Faqs/Admin/Faqs/Create', [
            'pages' => $this->pageOptions(),
        ]);
    }

    public function store(StoreFaqRequest $request)
    {
        $faq = Faq::create($this->normalizePageSlug($request->validated()));

        return redirect()
            ->route('admin.faqs.edit', $faq)
            ->with('success', 'Faq created.');
    }

    public function edit(Faq $faq): Response
    {
        $this->authorize('update', $faq);

        return Inertia::render('Faqs/Admin/Faqs/Edit', [
            'faq' => $faq,
            'pages' => $this->pageOptions(),
        ]);
    }

    public function update(UpdateFaqRequest $request, Faq $faq)
    {
        $faq->update($this->normalizePageSlug($request->validated()));

        return back()->with('success', 'Faq updated.');
    }

    public function destroy(Faq $faq)
    {
        $this->authorize('delete', $faq);

        $faq->delete();

        return redirect()
            ->route('admin.faqs.index')
            ->with('success', 'Faq deleted.');
    }

    /**
     * @return list<array{slug: string, title: string}>
     */
    private function pageOptions(): array
    {
        return collect($this->jsonData->list('pages'))->map(function (string $slug) {
            $data = $this->jsonData->get("pages/{$slug}");

            return [
                'slug' => $slug,
                'title' => is_string($data['title'] ?? null) && $data['title'] !== ''
                    ? $data['title']
                    : $slug,
            ];
        })->values()->all();
    }

    /**
     * Empty / "__global__" page_slug → null (global FAQ).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizePageSlug(array $data): array
    {
        $slug = $data['page_slug'] ?? null;
        if (! is_string($slug) || $slug === '' || $slug === '__global__') {
            $data['page_slug'] = null;
        }

        return $data;
    }
}

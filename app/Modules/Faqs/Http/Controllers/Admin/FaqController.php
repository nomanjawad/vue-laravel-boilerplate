<?php

namespace App\Modules\Faqs\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Faqs\Http\Requests\StoreFaqRequest;
use App\Modules\Faqs\Http\Requests\UpdateFaqRequest;
use App\Modules\Faqs\Models\Faq;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Faq::class);

        return Inertia::render('Faqs/Admin/Faqs/Index', [
            'faqs' => Faq::query()
                ->when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'filters' => $request->only('search'),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Faq::class);

        return Inertia::render('Faqs/Admin/Faqs/Create');
    }

    public function store(StoreFaqRequest $request)
    {
        $faq = Faq::create($request->validated());

        return redirect()
            ->route('admin.faqs.edit', $faq)
            ->with('success', 'Faq created.');
    }

    public function edit(Faq $faq): Response
    {
        $this->authorize('update', $faq);

        return Inertia::render('Faqs/Admin/Faqs/Edit', [
            'faq' => $faq,
        ]);
    }

    public function update(UpdateFaqRequest $request, Faq $faq)
    {
        $faq->update($request->validated());

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
}

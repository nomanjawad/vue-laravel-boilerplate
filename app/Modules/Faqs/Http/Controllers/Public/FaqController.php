<?php

namespace App\Modules\Faqs\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\Faqs\Models\Faq;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Faqs/Public/Faqs/Index', [
            'faqs' => Faq::query()
                ->where('is_active', true)
                ->latest('published_at')
                ->paginate(12),
        ]);
    }

    public function show(Faq $faq): Response
    {
        abort_unless($faq->is_active, 404);

        return Inertia::render('Faqs/Public/Faqs/Show', [
            'faq' => $faq,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Inertia\Inertia;

class PageController extends Controller
{
    public function show(Page $page)
    {
        if (! $page->is_published) {
            abort(404);
        }

        return Inertia::render('Public/Page/Show', [
            'page' => $page,
        ]);
    }
}

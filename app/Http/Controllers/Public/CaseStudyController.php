<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CaseStudy;
use Inertia\Inertia;

class CaseStudyController extends Controller
{
    public function index()
    {
        return Inertia::render('Public/CaseStudies/Index', [
            'caseStudies' => CaseStudy::active()->orderBy('sort_order')->get(),
        ]);
    }

    public function show(CaseStudy $caseStudy)
    {
        if (! $caseStudy->is_active) {
            abort(404);
        }

        return Inertia::render('Public/CaseStudies/Show', [
            'caseStudy' => $caseStudy,
        ]);
    }
}

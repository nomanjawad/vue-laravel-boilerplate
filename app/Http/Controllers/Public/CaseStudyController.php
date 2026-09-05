<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CaseStudy;
use App\Models\Media;
use App\Support\LcpPreload;
use Inertia\Inertia;

class CaseStudyController extends Controller
{
    public function index()
    {
        $studies = CaseStudy::active()->orderBy('sort_order')->get();
        $map = Media::imagePayloadMap($studies->pluck('featured_image'));

        return Inertia::render('Public/CaseStudies/Index', [
            'caseStudies' => $studies->map(function (CaseStudy $cs) use ($map) {
                $row = $cs->toArray();
                $row['featured_image'] = $map[$cs->featured_image ?? '']
                    ?? Media::imagePayload($cs->featured_image);

                return $row;
            }),
        ]);
    }

    public function show(CaseStudy $caseStudy)
    {
        if (! $caseStudy->is_active) {
            abort(404);
        }

        $row = $caseStudy->toArray();
        $row['featured_image'] = Media::imagePayload($caseStudy->featured_image);

        return Inertia::render('Public/CaseStudies/Show', [
            'caseStudy' => $row,
            'lcpPreload' => LcpPreload::fromMedia(
                $row['featured_image'] ?? null,
                '(max-width: 768px) 100vw, (max-width: 1280px) 90vw, 1200px',
            ),
        ]);
    }
}

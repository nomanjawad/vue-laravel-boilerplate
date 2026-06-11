<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Career;
use App\Services\SeoService;
use Inertia\Inertia;

class CareerController extends Controller
{
    public function __construct(private SeoService $seo) {}

    public function index()
    {
        return Inertia::render('Public/Careers/Index', [
            'careers' => Career::active()->orderBy('sort_order')->get(),
        ]);
    }

    public function show(Career $career)
    {
        if (! $career->is_active) {
            abort(404);
        }

        return Inertia::render('Public/Careers/Show', [
            'career' => $career,
            'jsonLd' => [
                $this->seo->jobPosting($career),
                $this->seo->breadcrumbs([
                    ['name' => 'Home', 'url' => '/'],
                    ['name' => 'Careers', 'url' => '/careers'],
                    ['name' => $career->title, 'url' => "/careers/{$career->slug}"],
                ]),
            ],
        ]);
    }
}

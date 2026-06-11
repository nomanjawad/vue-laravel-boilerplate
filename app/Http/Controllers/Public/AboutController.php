<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Services\JsonDataService;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class AboutController extends Controller
{
    public function __construct(private JsonDataService $jsonData) {}

    public function index()
    {
        return Inertia::render('Public/About/Index', [
            'data' => $this->jsonData->get('about'),
            'teamMembers' => Schema::hasTable('teams') && config('template.features.teams')
                ? Team::active()->orderBy('sort_order')->get()
                : [],
        ]);
    }
}

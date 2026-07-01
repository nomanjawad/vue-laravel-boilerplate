<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request, AdminSearchService $search): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        return response()->json([
            'groups' => $search->search($query, $request->user()),
        ]);
    }
}

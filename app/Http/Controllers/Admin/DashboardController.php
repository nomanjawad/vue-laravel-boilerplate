<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotFoundLog;
use App\Models\Order;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'users' => User::count(),
                'posts' => Schema::hasTable('posts') ? Post::count() : 0,
                'products' => Schema::hasTable('products') ? Product::count() : 0,
                'orders' => Schema::hasTable('orders') ? Order::count() : 0,
            ],
            // Top missed URLs — feeds the redirect manager (Admin > Redirects).
            'topNotFound' => Schema::hasTable('not_found_logs')
                ? NotFoundLog::orderByDesc('hit_count')->limit(10)->get(['id', 'path', 'hit_count', 'last_seen_at'])
                : [],
        ]);
    }
}

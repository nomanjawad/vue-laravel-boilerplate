<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Product;
use App\Services\JsonDataService;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function __construct(private JsonDataService $jsonData) {}

    public function index()
    {
        return Inertia::render('Public/Home/Index', [
            'data' => $this->jsonData->get('home'),
            'featuredPosts' => Schema::hasTable('posts')
                ? Post::published()->latest('published_at')->take(3)->get(['id', 'title', 'slug', 'excerpt', 'featured_image', 'published_at'])
                : [],
            'featuredProducts' => Schema::hasTable('products')
                ? Product::active()->inStock()->latest()->take(4)->get(['id', 'name', 'slug', 'price', 'compare_price', 'featured_image'])
                : [],
        ]);
    }
}

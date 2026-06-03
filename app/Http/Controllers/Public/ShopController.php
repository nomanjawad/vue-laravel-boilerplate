<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Public/Shop/Index', [
            'products' => Product::active()
                ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
                ->when($request->category, fn ($q, $c) => $q->whereHas('category', fn ($cq) => $cq->where('slug', $c)))
                ->when($request->sort === 'price_asc', fn ($q) => $q->orderBy('price'))
                ->when($request->sort === 'price_desc', fn ($q) => $q->orderByDesc('price'))
                ->when(! $request->sort, fn ($q) => $q->latest())
                ->paginate(12)
                ->withQueryString(),
            'categories' => Category::has('products')->orderBy('name')->get(['id', 'name', 'slug']),
            'filters' => $request->only('search', 'category', 'sort'),
        ]);
    }

    public function show(Product $product)
    {
        if (! $product->is_active) {
            abort(404);
        }

        return Inertia::render('Public/Shop/Show', [
            'product' => $product->load('category:id,name,slug'),
            'relatedProducts' => Product::active()
                ->where('id', '!=', $product->id)
                ->when($product->category_id, fn ($q) => $q->where('category_id', $product->category_id))
                ->take(4)
                ->get(['id', 'name', 'slug', 'price', 'compare_price', 'featured_image']),
        ]);
    }
}

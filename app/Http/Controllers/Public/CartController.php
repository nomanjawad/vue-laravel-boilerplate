<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CartController extends Controller
{
    public function __construct(private CartService $cartService)
    {
    }

    public function show()
    {
        return Inertia::render('Public/Shop/Cart', [
            'items' => array_values($this->cartService->getItems()),
            'subtotal' => $this->cartService->getSubtotal(),
        ]);
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['integer', 'min:1'],
        ]);

        $this->cartService->add($request->product_id, $request->quantity ?? 1);

        return back()->with('success', 'Added to cart.');
    }

    public function update(Request $request)
    {
        $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        $this->cartService->update($request->product_id, $request->quantity);

        return back()->with('success', 'Cart updated.');
    }

    public function remove(int $productId)
    {
        $this->cartService->remove($productId);

        return back()->with('success', 'Item removed from cart.');
    }
}

<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Session;

class CartService
{
    public function getItems(): array
    {
        return Session::get('cart', []);
    }

    public function add(int $productId, int $quantity = 1): void
    {
        $cart = $this->getItems();
        $product = Product::findOrFail($productId);

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $quantity;
        } else {
            $cart[$productId] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => (float) $product->price,
                'featured_image' => $product->featured_image,
                'slug' => $product->slug,
                'quantity' => $quantity,
            ];
        }

        Session::put('cart', $cart);
    }

    public function update(int $productId, int $quantity): void
    {
        $cart = $this->getItems();

        if (isset($cart[$productId])) {
            if ($quantity <= 0) {
                unset($cart[$productId]);
            } else {
                $cart[$productId]['quantity'] = $quantity;
            }
        }

        Session::put('cart', $cart);
    }

    public function remove(int $productId): void
    {
        $cart = $this->getItems();
        unset($cart[$productId]);
        Session::put('cart', $cart);
    }

    public function clear(): void
    {
        Session::forget('cart');
    }

    public function getSubtotal(): float
    {
        return collect($this->getItems())->sum(fn ($item) => $item['price'] * $item['quantity']);
    }

    public function getCount(): int
    {
        return count($this->getItems());
    }
}

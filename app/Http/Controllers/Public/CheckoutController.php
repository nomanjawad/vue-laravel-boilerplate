<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $cartService,
        private OrderService $orderService
    ) {}

    public function show()
    {
        $items = $this->cartService->getItems();

        if (empty($items)) {
            return redirect()->route('shop.index')->with('error', 'Your cart is empty.');
        }

        return Inertia::render('Public/Shop/Checkout', [
            'items' => array_values($items),
            'subtotal' => $this->cartService->getSubtotal(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'shipping_address.line1' => ['required', 'string', 'max:255'],
            'shipping_address.line2' => ['nullable', 'string', 'max:255'],
            'shipping_address.city' => ['required', 'string', 'max:255'],
            'shipping_address.state' => ['nullable', 'string', 'max:255'],
            'shipping_address.zip' => ['required', 'string', 'max:20'],
            'shipping_address.country' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = $this->orderService->createOrder($validated, auth()->id());

        if ($result['success']) {
            return redirect()->route('order.confirmation', $result['order']->order_number)
                ->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    public function confirmation(string $orderNumber)
    {
        $order = \App\Models\Order::where('order_number', $orderNumber)
            ->where('user_id', auth()->id())
            ->with('items')
            ->firstOrFail();

        return Inertia::render('Public/Shop/OrderConfirmation', [
            'order' => $order,
        ]);
    }
}

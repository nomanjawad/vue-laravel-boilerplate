<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;

class OrderService
{
    public function __construct(
        private CartService $cartService,
        private DummyPaymentService $paymentService
    ) {}

    public function createOrder(array $customerData, ?int $userId = null): array
    {
        $items = $this->cartService->getItems();

        if (empty($items)) {
            return ['success' => false, 'message' => 'Cart is empty.'];
        }

        $subtotal = $this->cartService->getSubtotal();
        $tax = round($subtotal * 0.0, 2); // 0% tax default, configure per project
        $total = $subtotal + $tax;

        // Process dummy payment
        $payment = $this->paymentService->charge($total);

        // Create order
        $order = Order::create([
            'user_id' => $userId,
            'status' => $payment->success ? 'processing' : 'pending',
            'payment_status' => $payment->success ? 'paid' : 'failed',
            'payment_method' => 'dummy',
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'customer_name' => $customerData['customer_name'],
            'customer_email' => $customerData['customer_email'],
            'shipping_address' => $customerData['shipping_address'],
            'billing_address' => $customerData['billing_address'] ?? null,
            'notes' => $customerData['notes'] ?? null,
            'paid_at' => $payment->success ? now() : null,
        ]);

        // Create order items and decrement stock
        foreach ($items as $item) {
            $order->items()->create([
                'product_id' => $item['product_id'],
                'product_name' => $item['name'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
                'total' => $item['price'] * $item['quantity'],
            ]);

            Product::where('id', $item['product_id'])->decrement('stock_quantity', $item['quantity']);
        }

        if ($payment->success) {
            $this->cartService->clear();
        }

        return [
            'success' => $payment->success,
            'message' => $payment->message,
            'order' => $order,
        ];
    }
}

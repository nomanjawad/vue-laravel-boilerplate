<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Admin/Orders/Index', [
            'orders' => Order::with('user:id,name')
                ->when($request->status, fn ($q, $s) => $q->where('status', $s))
                ->when($request->search, fn ($q, $s) => $q->where('order_number', 'like', "%{$s}%")->orWhere('customer_name', 'like', "%{$s}%"))
                ->latest()
                ->paginate(15)
                ->withQueryString(),
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function show(Order $order)
    {
        return Inertia::render('Admin/Orders/Show', [
            'order' => $order->load(['items', 'user:id,name,email']),
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,processing,completed,cancelled,refunded'],
        ]);

        $order->update($validated);

        return back()->with('success', 'Order status updated.');
    }
}

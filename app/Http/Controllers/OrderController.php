<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;

class OrderController extends Controller
{
    /**
     * List orders for current client.
     */
    public function index(Request $request)
    {
        $orders = Order::where('client_id', $request->user()->id)
            ->with('product:id,title,image,price')
            ->latest()
            ->paginate(20);

        return response()->json($orders);
    }

    /**
     * Create an order.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $data['client_id'] = $request->user()->id;
        $data['status'] = 'pending';

        $order = Order::create($data);

        return response()->json(['message' => 'Created', 'order' => $order], 201);
    }

    /**
     * Show single order (ownership verified).
     */
    public function show(Request $request, $id)
    {
        $order = Order::with('product')->findOrFail($id);

        if ($order->client_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($order);
    }

    /**
     * Seller orders (only approved status for simplicity; adjust as needed).
     */
    public function sellerOrders(Request $request)
    {
        $sellerId = $request->user()->id;

        $orders = Order::whereHas('product', function ($q) use ($sellerId) {
            $q->where('seller_id', $sellerId);
        })->where('status', 'approved')
          ->with('product:id,title,price')
          ->latest()
          ->paginate(20);

        return response()->json($orders);
    }

    /**
     * Update order status (seller / admin).
     */
    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $data = $request->validate([
            'status' => 'required|in:pending,approved,shipped,delivered,cancelled'
        ]);

        $order->status = $data['status'];
        $order->save();

        return response()->json(['message' => 'Status updated', 'order' => $order]);
    }
}

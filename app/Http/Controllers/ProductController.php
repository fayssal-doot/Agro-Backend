<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    /**
     * List all approved products (public).
     */
    public function index()
    {
        $products = Product::where('status', 'approved')
            ->with('seller:id,name,location')
            ->latest()
            ->paginate(20);

        return response()->json($products);
    }

    /**
     * Show single product.
     */
    public function show($id)
    {
        $product = Product::with('seller:id,name,location')->findOrFail($id);
        // increment views (simple)
        $product->increment('views');
        return response()->json($product);
    }

    /**
     * Create product (seller).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'sometimes|string|max:100',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'image' => 'sometimes|url|max:500',
            'location' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:1000',
        ]);

        $data['seller_id'] = $request->user()->id;
        $data['status'] = 'pending'; // needs admin approval

        $product = Product::create($data);

        return response()->json(['message' => 'Created', 'product' => $product], 201);
    }

    /**
     * Update product (owner only).
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        // ownership check
        if ($product->seller_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'category' => 'sometimes|string|max:100',
            'price' => 'sometimes|numeric|min:0',
            'quantity' => 'sometimes|integer|min:0',
            'image' => 'sometimes|url|max:500',
            'location' => 'sometimes|string|max:255',
            'description' => 'sometimes|string|max:1000',
        ]);

        $product->update($data);

        return response()->json(['message' => 'Updated', 'product' => $product]);
    }

    /**
     * Delete product (owner only).
     */
    public function destroy(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        if ($product->seller_id !== $request->user()->id && $request->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $product->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * Seller stats.
     */
    public function stats(Request $request)
    {
        $sellerId = $request->user()->id;

        $views = Product::where('seller_id', $sellerId)->sum('views');
        $popular = Product::where('seller_id', $sellerId)->orderByDesc('views')->first();

        return response()->json([
            'views' => $views,
            'popular' => $popular
        ]);
    }
}

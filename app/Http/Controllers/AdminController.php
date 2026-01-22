<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;

class AdminController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Seller Approval
    |--------------------------------------------------------------------------
    */
    public function pendingSellers()
    {
        $sellers = User::whereIn('role', ['farmer', 'store_owner'])
            ->where('status', 'pending')
            ->get();

        return response()->json(['data' => $sellers]);
    }

    public function approveSeller($id)
    {
        $user = User::findOrFail($id);
        $user->status = 'active';
        $user->save();

        return response()->json(['message' => 'Seller approved']);
    }

    public function rejectSeller($id)
    {
        $user = User::findOrFail($id);
        $user->status = 'rejected';
        $user->save();

        return response()->json(['message' => 'Seller rejected']);
    }

    /*
    |--------------------------------------------------------------------------
    | Order validation
    |--------------------------------------------------------------------------
    */
    public function orders()
    {
        $orders = Order::with('client:id,name,email', 'product:id,title,price')
            ->latest()
            ->paginate(20);

        return response()->json($orders);
    }

    public function validateOrder($id)
    {
        $order = Order::findOrFail($id);
        $order->status = 'approved';
        $order->save();

        return response()->json(['message' => 'Order validated']);
    }

    /*
    |--------------------------------------------------------------------------
    | User management
    |--------------------------------------------------------------------------
    */
    public function users()
    {
        $users = User::latest()->paginate(30);
        return response()->json($users);
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'User deleted']);
    }

    /*
    |--------------------------------------------------------------------------
    | Product management
    |--------------------------------------------------------------------------
    */
    public function products()
    {
        $products = Product::with('seller:id,name')->latest()->paginate(30);
        return response()->json($products);
    }

    public function deleteProduct($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        return response()->json(['message' => 'Product deleted']);
    }

    /*
    |--------------------------------------------------------------------------
    | Basic reports
    |--------------------------------------------------------------------------
    */
    public function reports()
    {
        return response()->json([
            'total_users' => User::count(),
            'total_products' => Product::count(),
            'total_orders' => Order::count(),
            'orders_pending' => Order::where('status', 'pending')->count(),
            'sellers_pending' => User::where('status', 'pending')->count(),
        ]);
    }
}

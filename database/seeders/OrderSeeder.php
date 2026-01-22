<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $client = User::where('email', 'client@example.com')->first();
        $product = Product::first();

        if (!$client || !$product) return;

        Order::create([
            'client_id' => $client->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'status' => 'pending',
        ]);

        Order::create([
            'client_id' => $client->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'status' => 'approved',
        ]);
    }
}

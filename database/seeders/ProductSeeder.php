<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\User;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $farmer = User::where('email', 'farmer@example.com')->first();

        if (!$farmer) return;

        $products = [
            ['title' => 'Organic Tomatoes', 'category' => 'Vegetables', 'price' => 12.00, 'quantity' => 100, 'status' => 'approved', 'location' => 'Green Valley'],
            ['title' => 'Fresh Eggs (30pcs)', 'category' => 'Poultry', 'price' => 5.50, 'quantity' => 50, 'status' => 'approved', 'location' => 'Green Valley'],
            ['title' => 'Raw Honey 500g', 'category' => 'Honey', 'price' => 18.00, 'quantity' => 30, 'status' => 'approved', 'location' => 'Mountain Apiary'],
            ['title' => 'Olive Oil 1L', 'category' => 'Oils', 'price' => 22.00, 'quantity' => 25, 'status' => 'pending', 'location' => 'Southern Fields'],
        ];

        foreach ($products as $p) {
            Product::create(array_merge($p, ['seller_id' => $farmer->id]));
        }
    }
}

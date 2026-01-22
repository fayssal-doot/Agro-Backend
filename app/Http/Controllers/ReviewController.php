<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Review;

class ReviewController extends Controller
{
    /**
     * List reviews (optionally filter by product).
     */
    public function index(Request $request)
    {
        $query = Review::with('user:id,name');

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        return response()->json($query->latest()->paginate(20));
    }

    /**
     * Store a review.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'sometimes|string|max:1000'
        ]);

        $data['user_id'] = $request->user()->id;

        $review = Review::create($data);

        return response()->json(['message' => 'Created', 'review' => $review], 201);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Public list of reviews (supports ?productId=).
     */
    public function index(Request $request)
    {
        $query = Review::query();
        if ($request->filled('productId')) {
            $query->where('productId', $request->productId);
        }
        $reviews = $query->orderBy('created_at', 'desc')->limit(100)->get();

        return response()->json([
            'success' => true,
            'count' => $reviews->count(),
            'data' => $reviews,
        ]);
    }

    /**
     * Submit a review (authenticated user, FR-007).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'productId' => 'required|string',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ]);

        $product = Product::find($validated['productId']);
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan.',
            ], 422);
        }

        $user = auth('api')->user();

        $review = Review::create([
            'userId' => (string) $user->_id,
            'userName' => $user->name,
            'productId' => (string) $product->_id,
            'rating' => (int) $validated['rating'],
            'comment' => trim($validated['comment']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ulasan berhasil dikirim.',
            'data' => $review,
        ], 201);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Get products with NoSQL filtering & search (FR-003).
     */
    public function index(Request $request)
    {
        $query = Product::query();

        // Search by name (support both `search` per PRD and legacy `q`)
        $keyword = $request->input('search', $request->input('q'));
        if (!empty($keyword)) {
            $escaped = preg_quote($keyword, '/');
            $query->where('name', 'regex', "/{$escaped}/i");
        }

        // Filter by category
        if ($request->filled('category') && $request->category !== 'Semua') {
            $query->where('category', $request->category);
        }

        // Filter by type (prebuilt_pc or sparepart)
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by brand
        if ($request->filled('brand')) {
            $query->where('brand', $request->brand);
        }

        // Filter by price range
        if ($request->filled('min_price')) {
            $query->where('price', '>=', (int) $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', (int) $request->max_price);
        }

        // Filter by embedded specs (for Builder-compatible filtering)
        if ($request->filled('socket')) {
            $query->where('specs.socket', $request->socket);
        }
        if ($request->filled('ramType')) {
            $query->where('specs.ramType', strtoupper($request->ramType));
        }

        // Status filter (only Active by default for public catalog)
        if (!$request->boolean('include_drafts')) {
            $query->where('status', '!=', 'Draft');
        }

        // Sorting (support PRD `lowest|highest` plus legacy `price_asc|price_desc`)
        $sortBy = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');

        if (in_array($sortBy, ['price_asc', 'lowest'])) {
            $query->orderBy('price', 'asc');
        } elseif (in_array($sortBy, ['price_desc', 'highest'])) {
            $query->orderBy('price', 'desc');
        } else {
            $query->orderBy('created_at', $order === 'asc' ? 'asc' : 'desc');
        }

        $perPage = (int) $request->get('per_page', $request->get('limit', 12));
        $perPage = max(1, min($perPage, 100));
        $page = max(1, (int) $request->get('page', 1));

        $total = (clone $query)->count();
        $products = $query->skip(($page - 1) * $perPage)->limit($perPage)->get();

        return response()->json([
            'success' => true,
            'count' => $products->count(),
            'data' => $products,
            'meta' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    /**
     * Get single product detail with embedded benchmarks & specs (FR-005).
     */
    public function show($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }
}


<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Get active cart for current authenticated user.
     */
    public function getCart()
    {
        $userId = (string) auth('api')->id();
        $cart = Cart::where('userId', $userId)->first();

        if (!$cart) {
            return response()->json([
                'success' => true,
                'data' => [
                    'items' => [],
                    'total' => 0,
                    'itemCount' => 0,
                ],
            ]);
        }

        // Hydrate product details
        $items = $cart->items ?? [];
        $productIds = array_column($items, 'productId');
        $products = Product::whereIn('_id', $productIds)->get()->keyBy(function ($p) {
            return (string) $p->_id;
        });

        $hydratedItems = [];
        $total = 0;
        $totalCount = 0;

        foreach ($items as $item) {
            $p = $products->get($item['productId']);
            if (!$p) continue;

            $qty = (int) ($item['quantity'] ?? 1);
            $price = (int) $p->price;
            $subtotal = $price * $qty;
            $total += $subtotal;
            $totalCount += $qty;

            $hydratedItems[] = [
                'productId' => (string) $p->_id,
                'name' => $p->name,
                'category' => $p->category,
                'price' => $price,
                'image_url' => $p->image_url,
                'quantity' => $qty,
                'subtotal' => $subtotal,
                'selectedSpecs' => $item['selectedSpecs'] ?? [],
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $hydratedItems,
                'total' => $total,
                'itemCount' => $totalCount,
            ],
        ]);
    }

    /**
     * Add item to user's cart.
     */
    public function addToCart(Request $request)
    {
        $request->validate([
            'productId' => 'required|string',
            'quantity' => 'nullable|integer|min:1',
            'selectedSpecs' => 'nullable|array',
        ]);

        $userId = (string) auth('api')->id();
        $productId = $request->productId;
        $quantity = (int) ($request->quantity ?? 1);
        $selectedSpecs = $request->selectedSpecs ?? [];

        $product = Product::find($productId);
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan.',
            ], 422);
        }

        $cart = Cart::firstOrCreate(
            ['userId' => $userId],
            ['items' => []]
        );

        $items = $cart->items ?? [];
        $foundIndex = -1;

        foreach ($items as $index => $item) {
            if ($item['productId'] === $productId) {
                $foundIndex = $index;
                break;
            }
        }

        $existingQty = $foundIndex >= 0 ? (int) ($items[$foundIndex]['quantity'] ?? 0) : 0;
        if (isset($product->stock) && ($existingQty + $quantity) > (int) $product->stock) {
            return response()->json([
                'success' => false,
                'message' => "Stok {$product->name} tidak mencukupi (sisa {$product->stock}, di keranjang {$existingQty}).",
            ], 422);
        }

        if ($foundIndex >= 0) {
            $items[$foundIndex]['quantity'] = ((int) $items[$foundIndex]['quantity']) + $quantity;
        } else {
            $items[] = [
                'productId' => $productId,
                'quantity' => $quantity,
                'selectedSpecs' => $selectedSpecs,
            ];
        }

        $cart->items = $items;
        $cart->save();

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan ke keranjang.',
        ]);
    }

    /**
     * Update item quantity in cart (set absolute quantity; 0 removes the item).
     */
    public function updateCartItem(Request $request, $productId)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:0|max:99',
        ]);

        $userId = (string) auth('api')->id();
        $cart = Cart::where('userId', $userId)->first();

        if (!$cart) {
            return response()->json([
                'success' => false,
                'message' => 'Keranjang kosong.',
            ], 404);
        }

        $items = $cart->items ?? [];
        $foundIndex = -1;
        foreach ($items as $index => $item) {
            if (($item['productId'] ?? null) === $productId) {
                $foundIndex = $index;
                break;
            }
        }

        if ($foundIndex < 0) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ada di keranjang.',
            ], 404);
        }

        $quantity = (int) $validated['quantity'];
        if ($quantity === 0) {
            unset($items[$foundIndex]);
            $cart->items = array_values($items);
            $cart->save();

            return response()->json([
                'success' => true,
                'message' => 'Produk dihapus dari keranjang.',
            ]);
        }

        $product = Product::find($productId);
        if ($product && isset($product->stock) && $quantity > (int) $product->stock) {
            return response()->json([
                'success' => false,
                'message' => "Stok {$product->name} tidak mencukupi (sisa {$product->stock}).",
            ], 422);
        }

        $items[$foundIndex]['quantity'] = $quantity;
        $cart->items = array_values($items);
        $cart->save();

        return response()->json([
            'success' => true,
            'message' => 'Jumlah produk diperbarui.',
        ]);
    }

    /**
     * Remove item from cart.
     */
    public function removeFromCart($productId)
    {
        $userId = (string) auth('api')->id();
        $cart = Cart::where('userId', $userId)->first();

        if ($cart) {
            $items = array_values(array_filter($cart->items ?? [], function ($item) use ($productId) {
                return $item['productId'] !== $productId;
            }));
            $cart->items = $items;
            $cart->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Produk dihapus dari keranjang.',
        ]);
    }

    /**
     * Clear all items from cart.
     */
    public function clearCart()
    {
        $userId = (string) auth('api')->id();
        Cart::where('userId', $userId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Keranjang dikosongkan.',
        ]);
    }
}


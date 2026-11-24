<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Validate a promo voucher code.
     */
    public function validateVoucher(Request $request)
    {
        $code = strtoupper(trim($request->input('code', '')));
        $subtotal = (int) $request->input('subtotal', 0);

        $voucher = Voucher::where('code', $code)->where('isActive', true)->first();

        if (!$voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Kode voucher tidak valid atau sudah kadaluarsa.',
            ], 404);
        }

        if ($voucher->expiredAt && now()->gt(\Carbon\Carbon::parse($voucher->expiredAt))) {
            return response()->json([
                'success' => false,
                'message' => 'Kode voucher sudah kedaluwarsa.',
            ], 422);
        }

        if ($subtotal < (int) $voucher->minPurchase) {
            $formattedMin = number_format($voucher->minPurchase, 0, ',', '.');
            return response()->json([
                'success' => false,
                'message' => "Minimal belanja untuk voucher ini adalah Rp {$formattedMin}.",
            ], 422);
        }

        $discount = 0;
        if ($voucher->discountPercentage > 0) {
            $discount = (int) round(($subtotal * $voucher->discountPercentage) / 100);
        } elseif ($voucher->discountAmount > 0) {
            $discount = (int) $voucher->discountAmount;
        }

        return response()->json([
            'success' => true,
            'message' => 'Voucher berhasil diterapkan!',
            'data' => [
                'code' => $voucher->code,
                'discountPercentage' => $voucher->discountPercentage,
                'discountAmount' => $discount,
            ],
        ]);
    }

    /**
     * Create checkout order (FR-006).
     */
    public function checkout(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'shippingDetails' => 'required|array',
            'shippingDetails.courier' => 'required|string',
            'shippingDetails.address' => 'required|string',
            'paymentMethod' => 'nullable|string',
            'voucherCode' => 'nullable|string',
        ]);

        $user = auth('api')->user();
        $userId = $user ? (string) $user->_id : null;

        // Calculate subtotal from products
        $itemProductIds = array_column($request->items, 'productId');
        $products = Product::whereIn('_id', $itemProductIds)->get()->keyBy(function ($p) {
            return (string) $p->_id;
        });

        $orderItems = [];
        $subtotal = 0;

        foreach ($request->items as $item) {
            $product = $products->get($item['productId']);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk tidak ditemukan.',
                    'errors' => ['items' => ["productId {$item['productId']} tidak valid."]],
                ], 422);
            }

            $qty = (int) ($item['quantity'] ?? 1);
            if ($qty < 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kuantitas tidak valid.',
                ], 422);
            }

            if (isset($product->stock) && (int) $product->stock < $qty) {
                return response()->json([
                    'success' => false,
                    'message' => "Stok {$product->name} tidak mencukupi (sisa {$product->stock}).",
                ], 422);
            }

            $price = (int) $product->price;
            $name = $product->name;

            $subtotal += ($price * $qty);
            $orderItems[] = [
                'productId' => $item['productId'],
                'name' => $name,
                'priceAtPurchase' => $price,
                'quantity' => $qty,
                'selectedSpecs' => $item['selectedSpecs'] ?? [],
            ];
        }

        // Apply voucher if present
        $discount = 0;
        $voucherId = null;
        if ($request->filled('voucherCode')) {
            $voucher = Voucher::where('code', strtoupper(trim($request->voucherCode)))
                ->where('isActive', true)
                ->first();

            if (!$voucher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode voucher tidak valid.',
                ], 422);
            }

            if ($voucher->expiredAt && now()->gt(\Carbon\Carbon::parse($voucher->expiredAt))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode voucher sudah kedaluwarsa.',
                ], 422);
            }

            if ($subtotal < (int) $voucher->minPurchase) {
                $formattedMin = number_format($voucher->minPurchase, 0, ',', '.');

                return response()->json([
                    'success' => false,
                    'message' => "Minimal belanja untuk voucher ini adalah Rp {$formattedMin}.",
                ], 422);
            }

            $voucherId = (string) $voucher->_id;
            if ($voucher->discountPercentage > 0) {
                $discount = (int) round(($subtotal * $voucher->discountPercentage) / 100);
            } else {
                $discount = (int) $voucher->discountAmount;
            }
        }

        $totalAmount = max(0, $subtotal - $discount);
        do {
            $orderNumber = '#RG-' . date('Ymd') . random_int(1000, 9999);
        } while (Order::where('orderNumber', $orderNumber)->exists());

        $order = Order::create([
            'orderNumber' => $orderNumber,
            'userId' => $userId,
            'customerName' => $user ? $user->name : ($request->input('customerName', 'Tamu')),
            'customerEmail' => $user ? $user->email : ($request->input('customerEmail', '')),
            'totalAmount' => $totalAmount,
            'status' => 'Diproses',
            'shippingDetails' => $request->shippingDetails,
            'items' => $orderItems,
            'voucherId' => $voucherId,
            'voucherDiscount' => $discount,
            'paymentMethod' => $request->paymentMethod ?? 'Transfer Bank',
        ]);

        // Clear user's cart after successful checkout
        if ($userId) {
            Cart::where('userId', $userId)->delete();
        }

        // Decrement stock from DB (never trust frontend totals/stock)
        foreach ($orderItems as $ordered) {
            $orderedProduct = $products->get($ordered['productId']);
            if ($orderedProduct && isset($orderedProduct->stock)) {
                $orderedProduct->stock = max(0, (int) $orderedProduct->stock - (int) $ordered['quantity']);
                $orderedProduct->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Pesanan berhasil dibuat!',
            'data' => [
                'id' => (string) $order->_id,
                'orderNumber' => $order->orderNumber,
                'totalAmount' => $order->totalAmount,
                'status' => $order->status,
            ],
        ], 201);
    }

    /**
     * Get order history for current user.
     */
    public function userOrders()
    {
        $userId = (string) auth('api')->id();
        $orders = Order::where('userId', $userId)->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Cancel own order (only while Diproses / Menunggu Pembayaran).
     * Restores product stock. Admin cancellations go through PUT /admin/orders/:id.
     */
    public function cancelOrder($id)
    {
        $userId = (string) auth('api')->id();
        $order = Order::where('_id', $id)->where('userId', $userId)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan.',
            ], 404);
        }

        if (!in_array($order->status, ['Diproses', 'Menunggu Pembayaran'])) {
            return response()->json([
                'success' => false,
                'message' => "Pesanan dengan status {$order->status} tidak bisa dibatalkan.",
            ], 422);
        }

        $order->status = 'Dibatalkan';
        $order->save();

        foreach ($order->items ?? [] as $item) {
            if (!isset($item['productId'])) {
                continue;
            }
            $product = Product::find($item['productId']);
            if ($product && isset($product->stock)) {
                $product->stock = (int) $product->stock + (int) ($item['quantity'] ?? 1);
                $product->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Pesanan berhasil dibatalkan.',
            'data' => $order,
        ]);
    }
}


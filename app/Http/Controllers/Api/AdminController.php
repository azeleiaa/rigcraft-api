<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Dashboard statistics & aggregations (FR-007).
     */
    public function dashboardStats()
    {
        // 1. Total revenue & count from orders
        $totalOrders = Order::count();
        $totalRevenue = Order::where('status', '!=', 'Dibatalkan')->sum('totalAmount');

        // Total products sold
        $allOrders = Order::where('status', '!=', 'Dibatalkan')->get();
        $productsSold = 0;
        foreach ($allOrders as $ord) {
            foreach ($ord->items ?? [] as $item) {
                $productsSold += (int) ($item['quantity'] ?? 1);
            }
        }

        // New customers
        $newCustomers = User::where('role', 'customer')->count();

        // 2. Order status breakdown
        $statusCounts = [
            'Semua' => $totalOrders,
            'Menunggu' => Order::where('status', 'Menunggu Pembayaran')->count(),
            'Diproses' => Order::where('status', 'Diproses')->count(),
            'Dikirim' => Order::where('status', 'Dikirim')->count(),
            'Selesai' => Order::where('status', 'Selesai')->count(),
            'Dibatalkan' => Order::where('status', 'Dibatalkan')->count(),
        ];

        // 3. Recent orders
        $recentOrders = Order::orderBy('created_at', 'desc')->limit(6)->get()->map(function ($o) {
            return [
                'id' => $o->orderNumber ?? (string) $o->_id,
                'customer' => $o->customerName ?? 'Pelanggan',
                'product' => count($o->items ?? []) > 0 ? $o->items[0]['name'] . (count($o->items) > 1 ? ' +' . (count($o->items) - 1) . ' lainnya' : '') : 'Item',
                'total' => 'Rp ' . number_format($o->totalAmount, 0, ',', '.'),
                'status' => $o->status,
                'date' => $o->created_at ? $o->created_at->format('d M Y') : date('d M Y'),
            ];
        });

        // 4. Products count by category
        $sparepartCount = Product::where('type', 'sparepart')->count();
        $prebuiltCount = Product::where('type', 'prebuilt_pc')->count();
        $peripheralCount = Product::where('category', 'Peripheral')->count();
        $totalProducts = Product::count();

        $categoryDistribution = [
            [
                'name' => 'Sparepart',
                'percentage' => $totalProducts > 0 ? round(($sparepartCount / $totalProducts) * 100) : 0,
                'color' => '#00E5FF',
            ],
            [
                'name' => 'Prebuilt PC',
                'percentage' => $totalProducts > 0 ? round(($prebuiltCount / $totalProducts) * 100) : 0,
                'color' => '#A855F7',
            ],
            [
                'name' => 'Peripheral',
                'percentage' => $totalProducts > 0 ? round(($peripheralCount / $totalProducts) * 100) : 0,
                'color' => '#EC4899',
            ],
        ];

        // 5. Sales chart: revenue + order count per day for the last 7 days
        $salesChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->startOfDay();
            $nextDay = (clone $day)->addDay();
            $dayOrders = Order::where('created_at', '>=', $day)->where('created_at', '<', $nextDay)->where('status', '!=', 'Dibatalkan')->get();
            $dayRevenue = $dayOrders->sum('totalAmount');
            $salesChart[] = [
                'date' => $day->format('Y-m-d'),
                'label' => $day->format('d M'),
                'revenue' => (int) $dayRevenue,
                'orders' => $dayOrders->count(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    [
                        'label' => 'TOTAL PENDAPATAN',
                        'value' => 'Rp ' . number_format($totalRevenue, 0, ',', '.'),
                        'raw' => $totalRevenue,
                        'change' => null,
                        'changeType' => 'neutral',
                        'changeLabel' => 'semua waktu',
                    ],
                    [
                        'label' => 'TOTAL PESANAN',
                        'value' => number_format($totalOrders, 0, ',', '.'),
                        'raw' => $totalOrders,
                        'change' => null,
                        'changeType' => 'neutral',
                        'changeLabel' => 'semua waktu',
                    ],
                    [
                        'label' => 'PRODUK TERJUAL',
                        'value' => number_format($productsSold, 0, ',', '.'),
                        'raw' => $productsSold,
                        'change' => null,
                        'changeType' => 'neutral',
                        'changeLabel' => 'semua waktu',
                    ],
                    [
                        'label' => 'PELANGGAN BARU',
                        'value' => (string) $newCustomers,
                        'raw' => $newCustomers,
                        'change' => null,
                        'changeType' => 'neutral',
                        'changeLabel' => 'semua waktu',
                    ],
                ],
                'statusCounts' => $statusCounts,
                'recentOrders' => $recentOrders,
                'categoryDistribution' => $categoryDistribution,
                'salesChart' => $salesChart,
            ],
        ]);
    }

    /**
     * Admin: List all products (FR-002).
     */
    public function products(Request $request)
    {
        $products = Product::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * Admin: Create product (FR-002).
     */
    public function storeProduct(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|in:prebuilt_pc,sparepart,component',
            'category' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'status' => 'nullable|string|in:Aktif,Draft,Nonaktif',
            'image_url' => 'nullable|string|max:2000',
            'gallery_images' => 'nullable|array',
            'specs' => 'nullable|array',
            'gameBenchmarks' => 'nullable|array',
            'upgradeOptions' => 'nullable|array',
        ]);

        $specsError = $this->validateSpecsRequirement($validated['category'] ?? '', $validated['type'] ?? null, $validated['specs'] ?? []);
        if ($specsError) {
            return response()->json([
                'success' => false,
                'message' => $specsError,
            ], 422);
        }

        $type = $validated['type'] ?? (in_array($validated['category'], ['Prebuilt PC', 'Gaming PC', 'Office PC']) ? 'prebuilt_pc' : 'sparepart');
        if ($type === 'component') {
            $type = 'sparepart';
        }

        $product = Product::create([
            'name' => $validated['name'],
            'type' => $type,
            'category' => $validated['category'],
            'brand' => $validated['brand'] ?? '',
            'price' => (int) $validated['price'],
            'stock' => (int) $validated['stock'],
            'status' => $validated['status'] ?? 'Aktif',
            'image_url' => $validated['image_url'] ?? '',
            'gallery_images' => $validated['gallery_images'] ?? [],
            'specs' => $validated['specs'] ?? [],
            'gameBenchmarks' => $validated['gameBenchmarks'] ?? [],
            'upgradeOptions' => $validated['upgradeOptions'] ?? [],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan.',
            'data' => $product,
        ], 201);
    }

    /**
     * Admin: Update product (FR-002).
     */
    public function updateProduct(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|nullable|string|in:prebuilt_pc,sparepart,component',
            'category' => 'sometimes|required|string|max:255',
            'brand' => 'sometimes|nullable|string|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'stock' => 'sometimes|required|integer|min:0',
            'status' => 'sometimes|nullable|string|in:Aktif,Draft,Nonaktif',
            'image_url' => 'sometimes|nullable|string|max:2000',
            'gallery_images' => 'sometimes|nullable|array',
            'specs' => 'sometimes|nullable|array',
            'gameBenchmarks' => 'sometimes|nullable|array',
            'upgradeOptions' => 'sometimes|nullable|array',
        ]);

        if (array_key_exists('type', $validated) && $validated['type'] === 'component') {
            $validated['type'] = 'sparepart';
        }

        // Validate merged specs so partial updates cannot drop required keys
        $mergedCategory = $validated['category'] ?? $product->category;
        $mergedType = $validated['type'] ?? $product->type;
        $mergedSpecs = array_merge(is_array($product->specs) ? $product->specs : [], $validated['specs'] ?? []);
        $specsError = $this->validateSpecsRequirement($mergedCategory, $mergedType, $mergedSpecs);
        if ($specsError) {
            return response()->json([
                'success' => false,
                'message' => $specsError,
            ], 422);
        }

        if (array_key_exists('price', $validated)) {
            $validated['price'] = (int) $validated['price'];
        }
        if (array_key_exists('stock', $validated)) {
            $validated['stock'] = (int) $validated['stock'];
        }

        $product->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil diperbarui.',
            'data' => $product,
        ]);
    }

    /**
     * Admin: Delete product (FR-002).
     */
    public function deleteProduct($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan.',
            ], 404);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dihapus.',
        ]);
    }

    /**
     * Admin: List all orders (supports ?status= filter).
     */
    public function orders(Request $request)
    {
        $query = Order::query();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $orders = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'count' => $orders->count(),
            'data' => $orders,
        ]);
    }

    /**
     * Admin: List product reviews (FR-007, supports ?productId=).
     */
    public function reviews(Request $request)
    {
        $query = \App\Models\Review::query();
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
     * Admin: List vouchers.
     */
    public function vouchers()
    {
        $vouchers = \App\Models\Voucher::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'count' => $vouchers->count(),
            'data' => $vouchers,
        ]);
    }

    /**
     * Admin: Create voucher.
     */
    public function storeVoucher(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'discountAmount' => 'nullable|integer|min:0',
            'discountPercentage' => 'nullable|integer|min:0|max:100',
            'minPurchase' => 'nullable|integer|min:0',
            'isActive' => 'nullable|boolean',
            'expiredAt' => 'nullable|date',
        ]);

        $code = strtoupper(trim($validated['code']));
        if (\App\Models\Voucher::where('code', $code)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Kode voucher sudah digunakan.',
            ], 422);
        }

        if (empty($validated['discountAmount']) && empty($validated['discountPercentage'])) {
            return response()->json([
                'success' => false,
                'message' => 'Isi discountAmount atau discountPercentage.',
            ], 422);
        }

        $voucher = \App\Models\Voucher::create([
            'code' => $code,
            'discountAmount' => (int) ($validated['discountAmount'] ?? 0),
            'discountPercentage' => (int) ($validated['discountPercentage'] ?? 0),
            'minPurchase' => (int) ($validated['minPurchase'] ?? 0),
            'isActive' => $validated['isActive'] ?? true,
            'expiredAt' => $validated['expiredAt'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Voucher berhasil ditambahkan.',
            'data' => $voucher,
        ], 201);
    }

    /**
     * Admin: Update voucher.
     */
    public function updateVoucher(Request $request, $id)
    {
        $voucher = \App\Models\Voucher::find($id);
        if (!$voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Voucher tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'code' => 'sometimes|required|string|max:50',
            'discountAmount' => 'sometimes|nullable|integer|min:0',
            'discountPercentage' => 'sometimes|nullable|integer|min:0|max:100',
            'minPurchase' => 'sometimes|nullable|integer|min:0',
            'isActive' => 'sometimes|nullable|boolean',
            'expiredAt' => 'sometimes|nullable|date',
        ]);

        if (isset($validated['code'])) {
            $code = strtoupper(trim($validated['code']));
            $exists = \App\Models\Voucher::where('code', $code)->where('_id', '!=', $voucher->_id)->exists();
            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode voucher sudah digunakan.',
                ], 422);
            }
            $validated['code'] = $code;
        }

        $voucher->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Voucher berhasil diperbarui.',
            'data' => $voucher,
        ]);
    }

    /**
     * Admin: Delete voucher.
     */
    public function deleteVoucher($id)
    {
        $voucher = \App\Models\Voucher::find($id);
        if (!$voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Voucher tidak ditemukan.',
            ], 404);
        }
        $voucher->delete();

        return response()->json([
            'success' => true,
            'message' => 'Voucher berhasil dihapus.',
        ]);
    }

    /**
     * Admin: Update order status.
     */
    public function updateOrderStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:Diproses,Dikirim,Selesai,Dibatalkan,Menunggu Pembayaran',
        ]);

        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan.',
            ], 404);
        }

        $previous = $order->status;
        $order->status = $validated['status'];
        $order->save();

        // Restore stock when an admin cancels a previously active order
        if ($validated['status'] === 'Dibatalkan' && $previous !== 'Dibatalkan') {
            foreach ($order->items ?? [] as $item) {
                if (!isset($item['productId'])) {
                    continue;
                }
                $product = \App\Models\Product::find($item['productId']);
                if ($product && isset($product->stock)) {
                    $product->stock = (int) $product->stock + (int) ($item['quantity'] ?? 1);
                    $product->save();
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Status pesanan berhasil diperbarui.',
            'data' => $order,
        ]);
    }

    /**
     * Enforce the 3 key spec fields needed by the Builder (PRD §5.2 / FR-004).
     * Returns an error message string, or null when valid.
     */
    private function validateSpecsRequirement(string $category, ?string $type, array $specs): ?string
    {
        $cat = strtoupper(trim($category));
        $isCpu = str_contains($cat, 'CPU') || str_contains($cat, 'PROCESSOR');
        $isMobo = str_contains($cat, 'MOTHERBOARD') || str_contains($cat, 'MAINBOARD');
        $isRam = str_contains($cat, 'RAM') || str_contains($cat, 'MEMORY');
        $isGpu = str_contains($cat, 'GPU') || str_contains($cat, 'VGA') || str_contains($cat, 'GRAPHIC');
        $isPsu = str_contains($cat, 'PSU') || str_contains($cat, 'POWER');
        $isPrebuilt = ($type === 'prebuilt_pc') || str_contains($cat, 'GAMING PC') || str_contains($cat, 'OFFICE PC') || $cat === 'PREBUILT PC';

        if ($isCpu && empty($specs['socket'])) {
            return "specs.socket wajib diisi untuk kategori CPU/Processor (untuk validasi builder).";
        }
        if ($isMobo) {
            if (empty($specs['socket'])) {
                return "specs.socket wajib diisi untuk kategori Motherboard.";
            }
            if (empty($specs['ramType'])) {
                return "specs.ramType wajib diisi untuk kategori Motherboard (contoh DDR5).";
            }
        }
        if ($isRam && empty($specs['ramType'])) {
            return "specs.ramType wajib diisi untuk kategori RAM (contoh DDR5).";
        }
        if (($isGpu || $isPsu || $isPrebuilt) && !isset($specs['powerWattage'])) {
            return "specs.powerWattage wajib diisi untuk kategori {$category} (untuk kalkulasi daya builder).";
        }

        return null;
    }
}


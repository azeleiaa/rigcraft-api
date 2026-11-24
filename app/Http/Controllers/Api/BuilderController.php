<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class BuilderController extends Controller
{
    /**
     * Cross-reference component JSON specs for compatibility (FR-004).
     * Accepts both the legacy `{ components: {...} }` shape and the PRD
     * `{ processor, motherboard, ram, gpu, psu }` shape. Each value may be
     * a product ID string or `{ productId }` object.
     */
    public function validateBuild(Request $request)
    {
        $componentIds = $request->input('components', []);

        if (empty($componentIds)) {
            foreach (['processor', 'cpu', 'motherboard', 'mainboard', 'ram', 'memory', 'gpu', 'vga', 'psu', 'power'] as $key) {
                $value = $request->input($key);
                if (is_string($value) && $value !== '') {
                    $componentIds[$key] = $value;
                } elseif (is_array($value) && isset($value['productId'])) {
                    $componentIds[$key] = $value['productId'];
                } elseif (is_array($value) && isset($value['id'])) {
                    $componentIds[$key] = $value['id'];
                }
            }
        } else {
            // Normalize legacy map values that may be objects
            foreach ($componentIds as $key => $value) {
                if (is_array($value) && isset($value['productId'])) {
                    $componentIds[$key] = $value['productId'];
                }
            }
        }

        // Load all selected products by their IDs
        $products = Product::whereIn('_id', array_values($componentIds))->get()->keyBy(function ($item) {
            return (string) $item->_id;
        });

        // Group by category
        $cpu = null;
        $motherboard = null;
        $ram = null;
        $gpu = null;
        $psu = null;

        foreach ($componentIds as $categoryKey => $id) {
            $prod = $products->get($id);
            if (!$prod) continue;

            $cat = strtoupper($prod->category);
            if (str_contains($cat, 'CPU') || str_contains($cat, 'PROCESSOR')) $cpu = $prod;
            elseif (str_contains($cat, 'MOTHERBOARD') || str_contains($cat, 'MAINBOARD')) $motherboard = $prod;
            elseif (str_contains($cat, 'RAM') || str_contains($cat, 'MEMORY')) $ram = $prod;
            elseif (str_contains($cat, 'GPU') || str_contains($cat, 'VGA') || str_contains($cat, 'GRAPHIC')) $gpu = $prod;
            elseif (str_contains($cat, 'PSU') || str_contains($cat, 'POWER')) $psu = $prod;
        }

        $warnings = [];
        $checks = [];
        $issues = [];

        // 1. CPU & Motherboard Socket Compatibility
        if ($cpu && $motherboard) {
            $cpuSocket = strtolower(trim($cpu->specs['socket'] ?? ''));
            $moboSocket = strtolower(trim($motherboard->specs['socket'] ?? ''));

            if (!$cpuSocket || !$moboSocket) {
                $message = "Data socket belum lengkap: CPU atau motherboard tidak memiliki specs.socket sehingga kompatibilitas tidak bisa dipastikan.";
                $warnings[] = $message;
                $issues[] = ['rule' => 'socket', 'message' => $message];
                $checks['socket'] = false;
            } elseif ($cpuSocket !== $moboSocket) {
                $message = "Incompatible Socket: CPU menggunakan socket '{$cpu->specs['socket']}', sedangkan motherboard menggunakan socket '{$motherboard->specs['socket']}'.";
                $warnings[] = $message;
                $issues[] = ['rule' => 'socket', 'message' => $message];
                $checks['socket'] = false;
            } else {
                $checks['socket'] = true;
            }
        }

        // 2. RAM & Motherboard RAM Type Compatibility
        if ($ram && $motherboard) {
            $ramType = strtoupper(trim($ram->specs['ramType'] ?? ($ram->specs['memoryType'] ?? '')));
            $moboRamType = strtoupper(trim($motherboard->specs['ramType'] ?? ($motherboard->specs['memorySupport'] ?? '')));

            if (!$ramType || !$moboRamType) {
                $message = "Data tipe RAM belum lengkap sehingga kompatibilitas RAM tidak bisa dipastikan.";
                $warnings[] = $message;
                $issues[] = ['rule' => 'ram', 'message' => $message];
                $checks['ram'] = false;
            } elseif (!str_contains($moboRamType, $ramType)) {
                $message = "Incompatible RAM: RAM bertipe '{$ramType}' tidak kompatibel dengan slot motherboard '{$moboRamType}'.";
                $warnings[] = $message;
                $issues[] = ['rule' => 'ram', 'message' => $message];
                $checks['ram'] = false;
            } else {
                $checks['ram'] = true;
            }
        }

        // 3. Power Wattage vs PSU Capacity (PRD: maksimal 80% kapasitas PSU)
        $estimatedWattage = 75; // Base system wattage (fans, chipset, storage)
        if ($cpu) {
            $estimatedWattage += (int) ($cpu->specs['powerWattage'] ?? ($cpu->specs['tdp'] ?? 105));
        }
        if ($gpu) {
            $estimatedWattage += (int) ($gpu->specs['powerWattage'] ?? ($gpu->specs['tdp'] ?? 220));
        }
        if ($ram) {
            $estimatedWattage += 15;
        }

        $checks['wattage'] = true;
        if ($psu) {
            $psuWattage = (int) ($psu->specs['powerWattage'] ?? ($psu->specs['wattage'] ?? 0));
            if ($psuWattage <= 0) {
                $message = "Data daya PSU belum lengkap (specs.powerWattage kosong) sehingga kecukupan daya tidak bisa dipastikan.";
                $warnings[] = $message;
                $issues[] = ['rule' => 'wattage', 'message' => $message];
                $checks['wattage'] = false;
            } else {
                $safeLimit = $psuWattage * 0.8;

                if ($estimatedWattage > $safeLimit) {
                    $message = "Daya PSU Kurang: Estimasi konsumsi sistem ({$estimatedWattage}W) mendekati atau melebihi kapasitas rekomendasi PSU ({$psuWattage}W). Disarankan minimal " . ceil($estimatedWattage / 0.8) . "W.";
                    $warnings[] = $message;
                    $issues[] = ['rule' => 'wattage', 'message' => $message];
                    $checks['wattage'] = false;
                }
            }
        }

        $isCompatible = count($warnings) === 0;
        $recommendedPsu = (int) (ceil(($estimatedWattage / 0.7) / 50) * 50);

        return response()->json([
            'success' => true,
            // legacy snake_case (backward compatible)
            'is_compatible' => $isCompatible,
            'estimated_wattage' => $estimatedWattage,
            'recommended_psu_wattage' => $recommendedPsu,
            // PRD camelCase aliases
            'compatible' => $isCompatible,
            'issues' => $issues,
            'estimatedWattage' => $estimatedWattage,
            'recommendedPsuWattage' => $recommendedPsu,
            'checks' => $checks,
            'warnings' => $warnings,
        ]);
    }
}


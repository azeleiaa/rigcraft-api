<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Additive product seeding (B — tambah data, TANPA truncate).
 * Safe to re-run: existing names are skipped.
 * All specs use real-world values (socket / ramType / powerWattage present
 * so Builder validation FR-004 keeps working).
 */
class AdditionalProductsSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            // ---------------------------------------------------------
            // Spareparts — CPUs (socket pair for Builder demo)
            // ---------------------------------------------------------
            [
                'name' => 'AMD Ryzen 5 7600X',
                'type' => 'sparepart',
                'category' => 'CPU',
                'brand' => 'AMD',
                'price' => 4599000,
                'stock' => 40,
                'status' => 'Aktif',
                'image_url' => 'https://images.unsplash.com/photo-1591799264318-7e6ef8ddb7ea?auto=format&fit=crop&w=900&q=85',
                'specs' => [
                    'socket' => 'AM5',
                    'cores' => 6,
                    'threads' => 12,
                    'baseClock' => '4.7GHz',
                    'boostClock' => '5.3GHz',
                    'powerWattage' => 105,
                ],
            ],
            [
                'name' => 'Intel Core i5-14400F',
                'type' => 'sparepart',
                'category' => 'CPU',
                'brand' => 'Intel',
                'price' => 3099000,
                'stock' => 35,
                'status' => 'Aktif',
                'image_url' => 'https://images.unsplash.com/photo-1591799264318-7e6ef8ddb7ea?auto=format&fit=crop&w=900&q=85',
                'specs' => [
                    'socket' => 'LGA1700',
                    'cores' => 10,
                    'threads' => 16,
                    'baseClock' => '2.5GHz',
                    'boostClock' => '4.7GHz',
                    'powerWattage' => 65,
                ],
            ],
            // ---------------------------------------------------------
            // Motherboards (AM5+DDR5 vs LGA1700+DDR4 contrast pair)
            // ---------------------------------------------------------
            [
                'name' => 'MSI PRO B650M-A WiFi',
                'type' => 'sparepart',
                'category' => 'Motherboard',
                'brand' => 'MSI',
                'price' => 2899000,
                'stock' => 25,
                'status' => 'Aktif',
                'image_url' => 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=900&q=85',
                'specs' => [
                    'socket' => 'AM5',
                    'ramType' => 'DDR5',
                    'formFactor' => 'mATX',
                    'powerWattage' => 50,
                ],
            ],
            [
                'name' => 'Gigabyte B760M DS3H DDR4',
                'type' => 'sparepart',
                'category' => 'Motherboard',
                'brand' => 'Gigabyte',
                'price' => 1999000,
                'stock' => 28,
                'status' => 'Aktif',
                'image_url' => 'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=900&q=85',
                'specs' => [
                    'socket' => 'LGA1700',
                    'ramType' => 'DDR4',
                    'formFactor' => 'mATX',
                    'powerWattage' => 45,
                ],
            ],
            // ---------------------------------------------------------
            // RAM / GPU / SSD / PSU
            // ---------------------------------------------------------
            [
                'name' => 'G.Skill Trident Z5 RGB 32GB DDR5-6400',
                'type' => 'sparepart',
                'category' => 'RAM',
                'brand' => 'G.Skill',
                'price' => 2199000,
                'stock' => 60,
                'status' => 'Aktif',
                'image_url' => 'https://images.unsplash.com/photo-1562976540-1502c2145186?auto=format&fit=crop&w=900&q=85',
                'specs' => [
                    'ramType' => 'DDR5',
                    'capacity' => '32GB (2x16GB)',
                    'speed' => '6400MHz',
                    'latency' => 'CL32',
                    'powerWattage' => 15,
                ],
            ],
            [
                'name' => 'MSI RTX 4060 Ti Ventus 2X OC 8GB',
                'type' => 'sparepart',
                'category' => 'GPU',
                'brand' => 'MSI',
                'price' => 7499000,
                'stock' => 24,
                'status' => 'Aktif',
                'image_url' => 'https://images.unsplash.com/photo-1587202372775-e229f172b9d7?auto=format&fit=crop&w=900&q=85',
                'specs' => [
                    'vram' => '8GB GDDR6',
                    'boostClock' => '2565MHz',
                    'powerWattage' => 160,
                ],
            ],
            [
                'name' => 'WD Black SN850X 1TB',
                'type' => 'sparepart',
                'category' => 'SSD',
                'brand' => 'WD',
                'price' => 1549000,
                'stock' => 48,
                'status' => 'Aktif',
                'image_url' => 'https://images.unsplash.com/photo-1597872200969-2b65d56bd16b?auto=format&fit=crop&w=900&q=85',
                'specs' => [
                    'capacity' => '1TB',
                    'interface' => 'PCIe Gen4 NVMe',
                    'readSpeed' => '7300 MB/s',
                    'powerWattage' => 10,
                ],
            ],
            [
                'name' => 'Seasonic Focus GX-750',
                'type' => 'sparepart',
                'category' => 'PSU',
                'brand' => 'Seasonic',
                'price' => 1749000,
                'stock' => 22,
                'status' => 'Aktif',
                'image_url' => 'https://images.unsplash.com/photo-1587202372634-32705e3bf49c?auto=format&fit=crop&w=900&q=85',
                'specs' => [
                    'powerWattage' => 750,
                    'certification' => '80 Plus Gold',
                    'modular' => 'Fully Modular',
                ],
            ],
            // ---------------------------------------------------------
            // Prebuilt (mid-range 1080p gaming)
            // ---------------------------------------------------------
            [
                'name' => 'RigCraft Vanguard Lite',
                'type' => 'prebuilt_pc',
                'category' => 'Gaming PC',
                'brand' => 'RigCraft',
                'price' => 15999000,
                'stock' => 10,
                'status' => 'Aktif',
                'image_url' => 'https://images.unsplash.com/photo-1591488320449-011701bb6704?auto=format&fit=crop&w=1400&q=90',
                'gallery_images' => [
                    'https://images.unsplash.com/photo-1591488320449-011701bb6704?auto=format&fit=crop&w=1400&q=90',
                ],
                'specs' => [
                    'processor' => 'AMD Ryzen 5 7600',
                    'motherboard' => 'MSI PRO B650M-A WiFi',
                    'socket' => 'AM5',
                    'ram' => '16GB DDR5 5600MHz',
                    'ramType' => 'DDR5',
                    'gpu' => 'NVIDIA RTX 4060 8GB',
                    'storage' => '1TB NVMe Gen4 SSD',
                    'powerWattage' => 550,
                ],
                'gameBenchmarks' => [
                    ['gameName' => 'Cyberpunk 2077', 'setting' => '1080p Ultra', 'avgFps' => 58],
                    ['gameName' => 'Fortnite', 'setting' => '1080p Epic', 'avgFps' => 140],
                    ['gameName' => 'Valorant', 'setting' => '1080p High', 'avgFps' => 350],
                ],
                'upgradeOptions' => [
                    ['title' => 'Upgrade RAM', 'description' => 'To 32GB DDR5', 'additionalPrice' => 1100000],
                    ['title' => 'Upgrade GPU', 'description' => 'Ke RTX 4060 Ti 8GB', 'additionalPrice' => 2800000],
                ],
            ],
        ];

        $added = 0;
        $skipped = 0;

        foreach ($products as $data) {
            if (Product::where('name', $data['name'])->exists()) {
                $skipped++;
                continue;
            }
            Product::create($data);
            $added++;
        }

        $this->command?->info("AdditionalProductsSeeder: {$added} ditambahkan, {$skipped} dilewati (sudah ada).");
    }
}

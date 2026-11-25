<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Seeder aditif untuk kategori Cooling & Casing (tanpa truncate).
 * Safe to re-run: nama yang sudah ada akan dilewati.
 *
 * Kategori memakai penamaan persis seperti yang dipakai frontend:
 * - 'Cooling' (dipakai Catalog filter + Builder id 'cooling')
 * - 'Casing'  (dipakai Catalog filter + Builder id 'case')
 */
class CoolingCasingSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            // ---------------------------------------------------------
            // Cooling — AIO Liquid + Air Cooler
            // ---------------------------------------------------------
            [
                'name' => 'NZXT Kraken X63 280mm AIO Liquid Cooler',
                'type' => 'sparepart',
                'category' => 'Cooling',
                'brand' => 'NZXT',
                'price' => 2850000,
                'stock' => 25,
                'status' => 'Aktif',
                'image_url' => 'https://images.unsplash.com/photo-1587202372775-e229f172b9d7?auto=format&fit=crop&w=900&q=85',
                'specs' => [
                    'coolerType' => 'AIO Liquid',
                    'radiatorSize' => '280mm',
                    'fanCount' => 2,
                    'fanSize' => '140mm',
                    'socketSupport' => 'AM5, LGA1700, LGA1200, AM4',
                    'tdp' => 250,
                    'noiseLevel' => '21-38 dBA',
                    'powerWattage' => 10,
                ],
            ],
            [
                'name' => 'Corsair iCUE H150i Elite 360mm AIO',
                'type' => 'sparepart',
                'category' => 'Cooling',
                'brand' => 'Corsair',
                'price' => 2799000,
                'stock' => 20,
                'status' => 'Aktif',
                'image_url' => 'https://images.unsplash.com/photo-1587202372634-32705e3bf49c?auto=format&fit=crop&w=900&q=85',
                'specs' => [
                    'coolerType' => 'AIO Liquid',
                    'radiatorSize' => '360mm',
                    'fanCount' => 3,
                    'fanSize' => '120mm',
                    'socketSupport' => 'AM5, AM4, LGA1700, LGA1200',
                    'tdp' => 300,
                    'noiseLevel' => '10-37 dBA',
                    'powerWattage' => 12,
                ],
            ],
            [
                'name' => 'DeepCool AK400 Zero Dark Air Cooler',
                'type' => 'sparepart',
                'category' => 'Cooling',
                'brand' => 'DeepCool',
                'price' => 450000,
                'stock' => 60,
                'status' => 'Aktif',
                'image_url' => 'https://images.unsplash.com/photo-1591488320449-011701bb6704?auto=format&fit=crop&w=900&q=85',
                'specs' => [
                    'coolerType' => 'Air Tower',
                    'fanSize' => '120mm',
                    'height' => '155mm',
                    'socketSupport' => 'AM5, AM4, LGA1700, LGA1200',
                    'tdp' => 220,
                    'noiseLevel' => '29 dBA',
                    'powerWattage' => 5,
                ],
            ],
            [
                'name' => 'Noctua NH-D15 G2 Dual Tower Air Cooler',
                'type' => 'sparepart',
                'category' => 'Cooling',
                'brand' => 'Noctua',
                'price' => 1799000,
                'stock' => 15,
                'status' => 'Aktif',
                'image_url' => 'https://images.unsplash.com/photo-1591799264318-7e6ef8ddb7ea?auto=format&fit=crop&w=900&q=85',
                'specs' => [
                    'coolerType' => 'Air Dual Tower',
                    'fanSize' => '140mm',
                    'height' => '168mm',
                    'socketSupport' => 'AM5, AM4, LGA1700, LGA1200, LGA1851',
                    'tdp' => 280,
                    'noiseLevel' => '24.8 dBA',
                    'powerWattage' => 5,
                ],
            ],

            // ---------------------------------------------------------
            // Casing — Mid Tower / ATX
            // ---------------------------------------------------------
            [
                'name' => 'NZXT H7 Flow RGB Mid Tower',
                'type' => 'sparepart',
                'category' => 'Casing',
                'brand' => 'NZXT',
                'price' => 2199000,
                'stock' => 18,
                'status' => 'Aktif',
                'image_url' => 'https://images.unsplash.com/photo-1587202372775-e229f172b9d7?auto=format&fit=crop&w=900&q=85',
                'specs' => [
                    'formFactor' => 'Mid Tower',
                    'motherboardSupport' => 'ATX, mATX, ITX',
                    'maxGpuLength' => '400mm',
                    'maxCoolerHeight' => '185mm',
                    'radiatorSupport' => 'Up to 360mm front / 280mm top',
                    'sidePanel' => 'Tempered Glass',
                    'powerWattage' => 0,
                ],
            ],
            [
                'name' => 'Lian Li O11 Dynamic EVO Mid Tower',
                'type' => 'sparepart',
                'category' => 'Casing',
                'brand' => 'Lian Li',
                'price' => 2799000,
                'stock' => 12,
                'status' => 'Aktif',
                'image_url' => 'https://images.unsplash.com/photo-1591488320449-011701bb6704?auto=format&fit=crop&w=900&q=85',
                'specs' => [
                    'formFactor' => 'Mid Tower',
                    'motherboardSupport' => 'E-ATX, ATX, mATX, ITX',
                    'maxGpuLength' => '426mm',
                    'maxCoolerHeight' => '167mm',
                    'radiatorSupport' => 'Up to 360mm top / side / bottom',
                    'sidePanel' => 'Tempered Glass',
                    'powerWattage' => 0,
                ],
            ],
            [
                'name' => 'Corsair 4000D Airflow Mid Tower',
                'type' => 'sparepart',
                'category' => 'Casing',
                'brand' => 'Corsair',
                'price' => 1599000,
                'stock' => 22,
                'status' => 'Aktif',
                'image_url' => 'https://images.unsplash.com/photo-1593640408182-31c70c8268f5?auto=format&fit=crop&w=900&q=85',
                'specs' => [
                    'formFactor' => 'Mid Tower',
                    'motherboardSupport' => 'ATX, mATX, ITX',
                    'maxGpuLength' => '360mm',
                    'maxCoolerHeight' => '170mm',
                    'radiatorSupport' => 'Up to 360mm front / 280mm roof',
                    'sidePanel' => 'Tempered Glass',
                    'powerWattage' => 0,
                ],
            ],
            [
                'name' => 'Fractal Design Pop Silent Mid Tower',
                'type' => 'sparepart',
                'category' => 'Casing',
                'brand' => 'Fractal Design',
                'price' => 1450000,
                'stock' => 16,
                'status' => 'Aktif',
                'image_url' => 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?auto=format&fit=crop&w=900&q=85',
                'specs' => [
                    'formFactor' => 'Mid Tower',
                    'motherboardSupport' => 'ATX, mATX, ITX',
                    'maxGpuLength' => '355mm',
                    'maxCoolerHeight' => '170mm',
                    'radiatorSupport' => 'Up to 280mm front',
                    'sidePanel' => 'Solid Steel (Silent)',
                    'powerWattage' => 0,
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

        $this->command?->info("CoolingCasingSeeder: {$added} ditambahkan, {$skipped} dilewati (sudah ada).");
    }
}

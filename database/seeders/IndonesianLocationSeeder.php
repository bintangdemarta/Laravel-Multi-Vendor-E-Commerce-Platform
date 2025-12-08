<?php

namespace Database\Seeders;

use App\Services\Shipping\RajaOngkirService;
use App\Models\Province;
use App\Models\City;
use App\Models\District;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class IndonesianLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * This seeder imports Indonesian location data from RajaOngkir API:
     * - 34 Provinces
     * - 500+ Cities/Regencies
     * - Sub-districts for major cities (to save time)
     * 
     * Note: Requires valid RajaOngkir API key in .env
     * Estimated time: 5-10 minutes
     */
    public function run(): void
    {
        $rajaongkir = app(RajaOngkirService::class);

        $this->command->info('🚀 Starting Indonesian location data import...');
        $this->command->newLine();

        // ========================================
        // Step 1: Import Provinces (34 provinces)
        // ========================================
        $this->command->info('📍 Importing provinces...');

        $provincesResult = $rajaongkir->getProvinces();

        if ($provincesResult['success']) {
            $provinceCount = 0;

            foreach ($provincesResult['provinces'] as $prov) {
                Province::updateOrCreate(
                    ['rajaongkir_id' => $prov['province_id']],
                    [
                        'name' => $prov['province'],
                        'code' => strtoupper(substr($prov['province'], 0, 3)),
                    ]
                );
                $provinceCount++;
            }

            $this->command->info("✅ {$provinceCount} provinces imported");
        } else {
            $this->command->error('❌ Failed to import provinces: ' . $provincesResult['error']);
            return;
        }

        $this->command->newLine();

        // ========================================
        // Step 2: Import Cities (500+ cities)
        // ========================================
        $this->command->info('🏙️  Importing cities...');

        $citiesResult = $rajaongkir->getCities();

        if ($citiesResult['success']) {
            $cityCount = 0;

            foreach ($citiesResult['cities'] as $city) {
                $province = Province::where('rajaongkir_id', $city['province_id'])->first();

                if ($province) {
                    City::updateOrCreate(
                        ['rajaongkir_id' => $city['city_id']],
                        [
                            'province_id' => $province->id,
                            'name' => $city['city_name'],
                            'type' => strtolower($city['type']), // kota or kabupaten
                            'postal_code' => $city['postal_code'] ?? null,
                        ]
                    );
                    $cityCount++;
                }
            }

            $this->command->info("✅ {$cityCount} cities imported");
        } else {
            $this->command->error('❌ Failed to import cities: ' . $citiesResult['error']);
            return;
        }

        $this->command->newLine();

        // ========================================
        // Step 3: Import Sub-districts (Major cities only)
        // ========================================
        $this->command->info('🏘️  Importing sub-districts for major cities...');
        $this->command->warn('⚠️  Only importing for major cities to save time and API quota');
        $this->command->newLine();

        // Major cities to import (based on RajaOngkir city_id)
        $majorCityIds = [
            '151' => 'Jakarta Pusat',
            '152' => 'Jakarta Utara',
            '153' => 'Jakarta Barat',
            '154' => 'Jakarta Selatan',
            '155' => 'Jakarta Timur',
            '39' => 'Bandung',
            '444' => 'Surabaya',
            '156' => 'Bekasi',
            '107' => 'Depok',
            '455' => 'Tangerang',
            '457' => 'Tangerang Selatan',
            '317' => 'Semarang',
            '419' => 'Surakarta (Solo)',
            '501' => 'Yogyakarta',
            '178' => 'Malang',
            '17' => 'Balikpapan',
            '278' => 'Palembang',
            '213' => 'Medan',
        ];

        $totalSubdistricts = 0;

        foreach ($majorCityIds as $cityId => $cityName) {
            $city = City::where('rajaongkir_id', $cityId)->first();

            if (!$city) {
                $this->command->warn("⚠️  City not found: {$cityName} (ID: {$cityId})");
                continue;
            }

            $this->command->info("   Processing: {$cityName}...");

            $subdistrictsResult = $rajaongkir->getSubdistricts($cityId);

            if ($subdistrictsResult['success']) {
                $count = 0;

                foreach ($subdistrictsResult['subdistricts'] as $sub) {
                    District::updateOrCreate(
                        ['rajaongkir_id' => $sub['subdistrict_id']],
                        [
                            'city_id' => $city->id,
                            'name' => $sub['subdistrict_name'],
                        ]
                    );
                    $count++;
                }

                $totalSubdistricts += $count;
                $this->command->info("   ✅ {$count} sub-districts imported for {$cityName}");
            } else {
                $this->command->error("   ❌ Failed: {$subdistrictsResult['error']}");
            }

            // Sleep to avoid rate limiting
            sleep(1);
        }

        $this->command->info("✅ Total {$totalSubdistricts} sub-districts imported");
        $this->command->newLine();

        // ========================================
        // Summary
        // ========================================
        $this->command->info('📊 Import Summary:');
        $this->command->table(
            ['Resource', 'Count'],
            [
                ['Provinces', Province::count()],
                ['Cities', City::count()],
                ['Sub-districts', District::count()],
            ]
        );

        $this->command->newLine();
        $this->command->info('🎉 Location data import completed successfully!');
        $this->command->newLine();
        $this->command->warn('💡 Tip: To import sub-districts for other cities, add their city_id to $majorCityIds array');
    }
}

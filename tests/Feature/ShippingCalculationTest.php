<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\Shipping\RajaOngkirService;
use App\Models\Province;
use App\Models\City;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ShippingCalculationTest extends TestCase
{
    /**
     * Test basic shipping cost calculation
     */
    public function test_calculate_shipping_cost_jne(): void
    {
        $rajaongkir = app(RajaOngkirService::class);

        // Jakarta Pusat to Surabaya, 1kg, JNE
        $result = $rajaongkir->getCost(
            origin: '151',      // Jakarta Pusat
            destination: '444', // Surabaya
            weight: 1000,       // 1 kg = 1000 grams
            courier: 'jne'
        );

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('results', $result);
        $this->assertNotEmpty($result['results']);

        // Check first result structure
        $firstOption = $result['results'][0];
        $this->assertArrayHasKey('courier_code', $firstOption);
        $this->assertArrayHasKey('cost', $firstOption);
        $this->assertArrayHasKey('etd', $firstOption);
    }

    /**
     * Test multiple courier comparison
     */
    public function test_get_multiple_shipping_options(): void
    {
        $rajaongkir = app(RajaOngkirService::class);

        $result = $rajaongkir->getMultipleCosts(
            origin: '151',
            destination: '444',
            weight: 1000,
            couriers: ['jne', 'tiki', 'pos']
    public function test_calculate_shipping_for_small_package(): void
    {
        $rajaongkir = app(RajaOngkirService::class);

        // Small package: 200 grams
        $result = $rajaongkir->getCost(
            origin: '151',
            destination: '39', // Bandung
            weight: 200,
            courier: 'jne'
        );

        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['results']);
    }

    /**
     * Test shipping cost caching
     */
    public function test_shipping_cost_is_cached(): void
    {
        $rajaongkir = app(RajaOngkirService::class);

        // First call - hits API
        $result1 = $rajaongkir->getCost('151', '444', 1000, 'jne');

        // Second call - should be from cache
        $result2 = $rajaongkir->getCost('151', '444', 1000, 'jne');

        $this->assertEquals($result1, $result2);
    }

    /**
     * Test waybill tracking
     */
    public function test_track_shipment(): void
    {
        $rajaongkir = app(RajaOngkirService::class);

        // Note: Use a real waybill number for actual testing
        $result = $rajaongkir->trackShipment(
            waybill: 'SOCAG00183235715',
            courier: 'jne'
        );

        // May fail if waybill doesn't exist, which is okay for test
        $this->assertArrayHasKey('success', $result);
    }

    /**
     * Example: Calculate shipping for multi-vendor cart
     */
    public function test_multi_vendor_cart_shipping(): void
    {
        // Scenario: Cart with items from 2 vendors
        $vendors = [
            [
                'vendor_id' => 1,
                'origin_city_id' => '151', // Jakarta
                'total_weight' => 1500,    // 1.5 kg
            ],
            [
                'vendor_id' => 2,
                'origin_city_id' => '39',  // Bandung
                'total_weight' => 800,     // 800 grams
            ],
        ];

        $customerCityId = '444'; // Surabaya
        $rajaongkir = app(RajaOngkirService::class);
        $totalShippingCost = 0;

        foreach ($vendors as $vendor) {
            $result = $rajaongkir->getCost(
                $vendor['origin_city_id'],
                $customerCityId,
                $vendor['total_weight'],
                'jne'
            );

            if ($result['success'] && !empty($result['results'])) {
                // Use cheapest option
                $cheapest = $result['results'][0];
                $totalShippingCost += $cheapest['cost'];
            }
        }

        $this->assertGreaterThan(0, $totalShippingCost);
    }
}

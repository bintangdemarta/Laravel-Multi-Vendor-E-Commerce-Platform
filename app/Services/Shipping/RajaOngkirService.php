<?php

namespace App\Services\Shipping;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * RajaOngkir Shipping Service
 * 
 * Handles shipping cost calculation using RajaOngkir Pro API
 * Requires: RajaOngkir Pro account for sub-district level
 */
class RajaOngkirService
{
    private string $apiKey;
    private string $baseUrl;
    private string $accountType;

    public function __construct()
    {
        $this->apiKey = config('services.rajaongkir.api_key');
        $this->baseUrl = config('services.rajaongkir.base_url');
        $this->accountType = config('services.rajaongkir.type');
    }

    /**
     * Get shipping cost from origin to destination
     * 
     * @param string $origin City ID (from vendor)
     * @param string $destination City ID (from customer address)
     * @param int $weight Weight in grams
     * @param string $courier Courier code (jne, tiki, pos, etc.)
     * @return array
     */
    public function getCost(string $origin, string $destination, int $weight, string $courier): array
    {
        // Generate cache key
        $cacheKey = "shipping_cost_{$origin}_{$destination}_{$weight}_{$courier}";

        // Try to get from cache
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $response = Http::withHeaders([
                'key' => $this->apiKey,
            ])->post("{$this->baseUrl}/cost", [
                        'origin' => $origin,
                        'destination' => $destination,
                        'weight' => $weight,
                        'courier' => $courier,
                    ]);

            if (!$response->successful()) {
                throw new \Exception('RajaOngkir API error: ' . $response->body());
            }

            $data = $response->json();

            if ($data['rajaongkir']['status']['code'] != 200) {
                throw new \Exception($data['rajaongkir']['status']['description']);
            }

            $result = [
                'success' => true,
                'origin' => $data['rajaongkir']['origin_details'],
                'destination' => $data['rajaongkir']['destination_details'],
                'results' => $this->formatCostResults($data['rajaongkir']['results']),
            ];

            // Cache for 24 hours
            $cacheTtl = config('marketplace.cache.shipping_rates_ttl', 86400);
            Cache::put($cacheKey, $result, $cacheTtl);

            return $result;

        } catch (\Exception $e) {
            Log::error('RajaOngkir API error', [
                'origin' => $origin,
                'destination' => $destination,
                'weight' => $weight,
                'courier' => $courier,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get multiple shipping options for checkout
     * 
     * @param string $origin
     * @param string $destination
     * @param int $weight
     * @param array $couriers Available couriers ['jne', 'tiki', 'pos']
     * @return array
     */
    public function getMultipleCosts(string $origin, string $destination, int $weight, array $couriers): array
    {
        $results = [];
        $errors = [];

        foreach ($couriers as $courier) {
            $cost = $this->getCost($origin, $destination, $weight, $courier);

            if ($cost['success']) {
                $results = array_merge($results, $cost['results']);
            } else {
                $errors[] = [
                    'courier' => $courier,
                    'error' => $cost['error'],
                ];
            }
        }

        return [
            'success' => !empty($results),
            'options' => $results,
            'errors' => $errors,
        ];
    }

    /**
     * Format cost results for easier consumption
     */
    private function formatCostResults(array $results): array
    {
        $formatted = [];

        foreach ($results as $result) {
            $courierCode = strtoupper($result['code']);
            $courierName = $result['name'];

            foreach ($result['costs'] as $cost) {
                $formatted[] = [
                    'courier_code' => $courierCode,
                    'courier_name' => $courierName,
                    'service' => $cost['service'],
                    'description' => $cost['description'],
                    'cost' => $cost['cost'][0]['value'],
                    'etd' => $cost['cost'][0]['etd'],
                    'note' => $cost['cost'][0]['note'] ?? '',
                ];
            }
        }

        // Sort by cost (cheapest first)
        usort($formatted, function ($a, $b) {
            return $a['cost'] <=> $b['cost'];
        });

        return $formatted;
    }

    /**
     * Get list of provinces from RajaOngkir
     */
    public function getProvinces(): array
    {
        $cacheKey = 'rajaongkir_provinces';

        return Cache::remember($cacheKey, 604800, function () { // Cache for 1 week
            try {
                $response = Http::withHeaders([
                    'key' => $this->apiKey,
                ])->get("{$this->baseUrl}/province");

                $data = $response->json();

                if ($data['rajaongkir']['status']['code'] != 200) {
                    throw new \Exception($data['rajaongkir']['status']['description']);
                }

                return [
                    'success' => true,
                    'provinces' => $data['rajaongkir']['results'],
                ];
            } catch (\Exception $e) {
                Log::error('RajaOngkir get provinces error', ['error' => $e->getMessage()]);
                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        });
    }

    /**
     * Get list of cities by province
     */
    public function getCities(string $provinceId = null): array
    {
        $cacheKey = $provinceId ? "rajaongkir_cities_{$provinceId}" : "rajaongkir_cities_all";

        return Cache::remember($cacheKey, 604800, function () use ($provinceId) {
            try {
                $url = "{$this->baseUrl}/city";
                if ($provinceId) {
                    $url .= "?province={$provinceId}";
                }

                $response = Http::withHeaders([
                    'key' => $this->apiKey,
                ])->get($url);

                $data = $response->json();

                if ($data['rajaongkir']['status']['code'] != 200) {
                    throw new \Exception($data['rajaongkir']['status']['description']);
                }

                return [
                    'success' => true,
                    'cities' => $data['rajaongkir']['results'],
                ];
            } catch (\Exception $e) {
                Log::error('RajaOngkir get cities error', ['error' => $e->getMessage()]);
                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        });
    }

    /**
     * Get list of sub-districts by city (Pro account only)
     */
    public function getSubdistricts(string $cityId): array
    {
        if ($this->accountType !== 'pro') {
            return [
                'success' => false,
                'error' => 'Sub-district data requires RajaOngkir Pro account',
            ];
        }

        $cacheKey = "rajaongkir_subdistricts_{$cityId}";

        return Cache::remember($cacheKey, 604800, function () use ($cityId) {
            try {
                $response = Http::withHeaders([
                    'key' => $this->apiKey,
                ])->get("{$this->baseUrl}/subdistrict?city={$cityId}");

                $data = $response->json();

                if ($data['rajaongkir']['status']['code'] != 200) {
                    throw new \Exception($data['rajaongkir']['status']['description']);
                }

                return [
                    'success' => true,
                    'subdistricts' => $data['rajaongkir']['results'],
                ];
            } catch (\Exception $e) {
                Log::error('RajaOngkir get subdistricts error', ['error' => $e->getMessage()]);
                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        });
    }

    /**
     * Track shipment (Waybill tracking)
     */
    public function trackShipment(string $waybill, string $courier): array
    {
        try {
            $response = Http::withHeaders([
                'key' => $this->apiKey,
            ])->post("{$this->baseUrl}/waybill", [
                        'waybill' => $waybill,
                        'courier' => strtolower($courier),
                    ]);

            $data = $response->json();

            if ($data['rajaongkir']['status']['code'] != 200) {
                throw new \Exception($data['rajaongkir']['status']['description']);
            }

            return [
                'success' => true,
                'tracking' => $data['rajaongkir']['result'],
            ];
        } catch (\Exception $e) {
            Log::error('RajaOngkir tracking error', [
                'waybill' => $waybill,
                'courier' => $courier,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Clear shipping cost cache
     */
    public function clearCache(string $pattern = 'shipping_cost_*'): void
    {
        // Note: This requires Redis or Memcached for pattern-based deletion
        Cache::forget($pattern);
    }
}

<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\CommissionService;
use App\Models\Vendor;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CommissionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CommissionService $commissionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->commissionService = app(CommissionService::class);
    }

    public function test_uses_default_commission_rate()
    {
        $vendor = Vendor::factory()->create(['commission_rate' => null]);
        $category = Category::factory()->create(['commission_rate' => null]);

        $order = (object) [
            'vendor' => $vendor,
            'category' => $category,
            'subtotal' => 100000,
        ];

        $result = $this->commissionService->calculateCommission($order);

        $this->assertEquals(0.10, $result['commission_rate']); // 10% default
        $this->assertEquals(10000, $result['commission_amount']);
        $this->assertEquals(90000, $result['vendor_earnings']);
    }

    public function test_uses_vendor_override_commission()
    {
        $vendor = Vendor::factory()->create(['commission_rate' => 0.08]); // 8%
        $category = Category::factory()->create(['commission_rate' => 0.12]);

        $order = (object) [
            'vendor' => $vendor,
            'category' => $category,
            'subtotal' => 100000,
        ];

        $result = $this->commissionService->calculateCommission($order);

        $this->assertEquals(0.08, $result['commission_rate']);
        $this->assertEquals(8000, $result['commission_amount']);
        $this->assertEquals(92000, $result['vendor_earnings']);
    }

    public function test_uses_category_commission_when_no_vendor_override()
    {
        $vendor = Vendor::factory()->create(['commission_rate' => null]);
        $category = Category::factory()->create(['commission_rate' => 0.15]); // 15%

        $order = (object) [
            'vendor' => $vendor,
            'category' => $category,
            'subtotal' => 100000,
        ];

        $result = $this->commissionService->calculateCommission($order);

        $this->assertEquals(0.15, $result['commission_rate']);
        $this->assertEquals(15000, $result['commission_amount']);
        $this->assertEquals(85000, $result['vendor_earnings']);
    }

    public function test_commission_priority_vendor_over_category()
    {
        $vendor = Vendor::factory()->create(['commission_rate' => 0.05]); // 5%
        $category = Category::factory()->create(['commission_rate' => 0.20]); // 20%

        $order = (object) [
            'vendor' => $vendor,
            'category' => $category,
            'subtotal' => 100000,
        ];

        $result = $this->commissionService->calculateCommission($order);

        // Should use vendor rate (5%), not category rate (20%)
        $this->assertEquals(0.05, $result['commission_rate']);
        $this->assertEquals(5000, $result['commission_amount']);
    }
}

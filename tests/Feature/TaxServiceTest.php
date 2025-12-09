<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\TaxService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TaxServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TaxService $taxService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->taxService = app(TaxService::class);
    }

    public function test_calculates_vat_correctly()
    {
        $order = (object) ['subtotal' => 100000];

        $tax = $this->taxService->calculateOrderTax($order);

        // 11% VAT
        $this->assertEquals(11000, $tax['vat_amount']);
    }

    public function test_calculates_marketplace_withholding_correctly()
    {
        $order = (object) ['subtotal' => 100000];

        $tax = $this->taxService->calculateOrderTax($order);

        // 2.5% marketplace withholding (PMK 37/2025)
        $this->assertEquals(2500, $tax['marketplace_withholding']);
    }

    public function test_calculates_total_tax()
    {
        $order = (object) ['subtotal' => 100000];

        $tax = $this->taxService->calculateOrderTax($order);

        // VAT (11%) + Withholding (2.5%) = 13.5%
        $this->assertEquals(13500, $tax['total_tax']);
    }

    public function test_validates_npwp_format()
    {
        // Valid NPWP
        $this->assertTrue($this->taxService->validateNPWP('12.345.678.9-012.345'));
        $this->assertTrue($this->taxService->validateNPWP('123456789012345')); // Without separators

        // Invalid NPWP
        $this->assertFalse($this->taxService->validateNPWP('12345')); // Too short
        $this->assertFalse($this->taxService->validateNPWP('ABCDEFGHIJKLMNO')); // Non-numeric
        $this->assertFalse($this->taxService->validateNPWP('12.345.678.9-012.34')); // Wrong length
    }

    public function test_formats_npwp_correctly()
    {
        $npwp = '123456789012345';
        $formatted = $this->taxService->formatNPWP($npwp);

        $this->assertEquals('12.345.678.9-012.345', $formatted);
    }
}

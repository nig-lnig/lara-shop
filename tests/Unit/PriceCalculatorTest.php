<?php

namespace Tests\Unit;

use App\Services\PriceCalculator;
use PHPUnit\Framework\TestCase;

class PriceCalculatorTest extends TestCase
{
    public function test_converts_between_decimal_strings_and_cents(): void
    {
        $this->assertSame(1250, PriceCalculator::toCents('12.50'));
        $this->assertSame(1250, PriceCalculator::toCents('12.5'));
        $this->assertSame(1200, PriceCalculator::toCents('12'));
        $this->assertSame('12.50', PriceCalculator::fromCents(1250));
        $this->assertSame('0.05', PriceCalculator::fromCents(5));
    }

    public function test_discount_is_rounded_half_up_to_the_centime(): void
    {
        // 0.10 x 5 % = 0.005 -> 0.01
        $this->assertSame(1, PriceCalculator::discount(10, 5));
        // 0.10 x 4 % = 0.004 -> 0.00
        $this->assertSame(0, PriceCalculator::discount(10, 4));
        $this->assertSame(2848, PriceCalculator::discount(28480, 10));
    }

    public function test_totals_avoid_floating_point_errors(): void
    {
        $totals = PriceCalculator::totals([
            ['unit_price' => '0.10', 'quantity' => 1],
            ['unit_price' => '0.20', 'quantity' => 1],
        ], 0);

        $this->assertSame('0.30', $totals['subtotal']);
        $this->assertSame('0.30', $totals['total']);
    }

    public function test_totals_with_discount(): void
    {
        $totals = PriceCalculator::totals([
            ['unit_price' => '49.90', 'quantity' => 3],
            ['unit_price' => '12.50', 'quantity' => 2],
        ], 15);

        $this->assertSame('149.70', $totals['lines'][0]['line_total']);
        $this->assertSame('174.70', $totals['subtotal']);
        $this->assertSame('26.21', $totals['discount_amount']); // 26.205 -> 26.21
        $this->assertSame('148.49', $totals['total']);
    }
}

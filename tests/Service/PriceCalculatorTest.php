<?php

namespace App\Tests\Service;

use App\Service\PriceCalculator;
use PHPUnit\Framework\TestCase;

class PriceCalculatorTest extends TestCase
{
    private PriceCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new PriceCalculator();
    }

    public function testCalculateWithoutCoupon(): void
    {
        $price = $this->calculator->calculate(
            $this->createProduct('100.00'),
            'DE123456789'
        );

        // 100 + 19% = 119.00
        self::assertSame('119.00', $price);
    }

    public function testCalculateWithFixedCoupon(): void
    {
        $coupon = $this->createCoupon('SALE6AMOUNT', 'fixed', '10.00');
        $price = $this->calculator->calculate(
            $this->createProduct('100.00'),
            'DE123456789',
            $coupon
        );

        // 100 - 10 = 90; 90 + 19% = 107.10
        self::assertSame('107.10', $price);
    }

    public function testCalculateWithPercentCoupon(): void
    {
        $coupon = $this->createCoupon('SALE6PERCENT', 'percent', '15.00');
        $price = $this->calculator->calculate(
            $this->createProduct('100.00'),
            'GR123456789',
            $coupon
        );

        // 100 - 15% = 85; 85 + 24% = 105.40
        self::assertSame('105.40', $price);
    }

    public function testCalculateWithNegativePrice(): void
    {
        $coupon = $this->createCoupon('BIG', 'fixed', '200.00');
        $price = $this->calculator->calculate(
            $this->createProduct('100.00'),
            'DE123456789',
            $coupon
        );

        // 100 - 200 = -100 → должно стать 0.00
        self::assertSame('0.00', $price);
    }

    public function testGetTaxRateGermany(): void
    {
        $rate = $this->invokeMethod($this->calculator, 'getTaxRate', ['DE123456789']);
        self::assertSame('0.19', $rate);
    }

    public function testGetTaxRateItaly(): void
    {
        $rate = $this->invokeMethod($this->calculator, 'getTaxRate', ['IT12345678900']);
        self::assertSame('0.22', $rate);
    }

    public function testGetTaxRateFrance(): void
    {
        $rate = $this->invokeMethod($this->calculator, 'getTaxRate', ['FRXX123456789']);
        self::assertSame('0.20', $rate);
    }

    public function testGetTaxRateGreece(): void
    {
        $rate = $this->invokeMethod($this->calculator, 'getTaxRate', ['GR123456789']);
        self::assertSame('0.24', $rate);
    }

    public function testGetTaxRateUnknown(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->invokeMethod($this->calculator, 'getTaxRate', ['XX123']);
    }

    private function createProduct(string $price): \App\Entity\Product
    {
        $product = new \App\Entity\Product();
        $product->setPrice($price);
        return $product;
    }

    private function createCoupon(string $code, string $type, string $value): \App\Entity\Coupon
    {
        $coupon = new \App\Entity\Coupon();
        $coupon->setCode($code);
        $coupon->setType(\App\Enum\CouponTypeEnum::from($type));
        $coupon->setValue($value);
        return $coupon;
    }

    private function invokeMethod(object $object, string $method, array $args = [])
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $args);
    }
}

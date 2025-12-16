<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\Coupon;
use App\Enum\CouponTypeEnum;

class PriceCalculator
{
    private const SCALE = 2;

    public function calculate(
        Product $product,
        string $taxNumber,
        ?Coupon $coupon = null
    ): string {
        $price = $product->getPrice();

        if (null !== $coupon) {
            $price = $this->applyCoupon($price, $coupon);
        }

        // if $price < 0
        if (bccomp($price, '0', self::SCALE) === -1) {
            $price = '0';
        }

        $taxRate = $this->getTaxRate($taxNumber);
        $taxAmount = bcmul($price, $taxRate, self::SCALE);
        $price = bcadd($price, $taxAmount, self::SCALE);

        return $price;
    }

    private function applyCoupon(string $price, Coupon $coupon): string
    {
        /** @var string $value */
        $value = $coupon->getValue();

        return match ($coupon->getType()) {
            CouponTypeEnum::Fixed => bcsub($price, $value, self::SCALE),

            CouponTypeEnum::Percent => $this->applyPercentDiscount(
                $price,
                $value
            ),
        };
    }

    private function applyPercentDiscount(string $price, string $percent): string
    {
        $rate = bcdiv($percent, '100', 4);

        $discount = bcmul($price, $rate, self::SCALE);

        return bcsub($price, $discount, self::SCALE);
    }

    private function getTaxRate(string $taxNumber): string
    {
        return match (true) {
            str_starts_with($taxNumber, 'DE') => '0.19',
            str_starts_with($taxNumber, 'IT') => '0.22',
            str_starts_with($taxNumber, 'GR') => '0.24',
            str_starts_with($taxNumber, 'FR') => '0.20',
            default => throw new \RuntimeException('Unknown tax number'),
        };
    }
}

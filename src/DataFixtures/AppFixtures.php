<?php

namespace App\DataFixtures;

use App\Entity\Coupon;
use App\Entity\Product;
use App\Enum\CouponTypeEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    const PRODUCTS = [
        ['Iphone', '100.00'],
        ['Наушники', '20.00'],
        ['Чехол', '10.00'],
        ['Dassault Falcon 50', '21150000.00'],
    ];

    const COUPONS = [
        ['SALE15PERCENT', CouponTypeEnum::Percent, '15.00'],
        ['SALE10AMOUNT', CouponTypeEnum::Fixed, '10.00'],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach(self::PRODUCTS as [$name, $price]) {
            $product = new Product();
            $product->setName($name);
            $product->setPrice($price);

            $manager->persist($product);
        }

        foreach(self::COUPONS as [$code, $type, $value]) {
            $coupon = new Coupon();
            $coupon->setCode($code);
            $coupon->setType($type);
            $coupon->setValue($value);

            $manager->persist($coupon);
        }

        $manager->flush();
    }
}

<?php

namespace App\Tests\Service;

use App\Entity\Coupon;
use App\Entity\Product;
use App\Exception\BusinessValidationException;
use App\Repository\CouponRepository;
use App\Repository\ProductRepository;
use App\Service\PriceManager;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\PhpUnit\ProphecyTrait;

class PriceManagerTest extends TestCase
{
    use ProphecyTrait;

    private PriceManager $priceManager;
    private ObjectProphecy $productRepository;
    private ObjectProphecy $couponRepository;
    private ObjectProphecy $priceCalculator;

    protected function setUp(): void
    {
        $this->productRepository = $this->prophesize(ProductRepository::class);
        $this->couponRepository = $this->prophesize(CouponRepository::class);
        $this->priceCalculator = $this->prophesize(\App\Service\PriceCalculator::class);

        $this->priceManager = new PriceManager(
            $this->productRepository->reveal(),
            $this->couponRepository->reveal(),
            $this->priceCalculator->reveal()
        );
    }

    public function testCalculatePriceProductNotFound(): void
    {
        $this->productRepository
            ->find(1)
            ->willReturn(null);

        $this->expectException(BusinessValidationException::class);
        $this->expectExceptionMessage('Product not found');
        $this->expectExceptionCode(0);

        $dto = new \App\DTO\CalculatePriceRequest();
        $dto->product = 1;
        $dto->taxNumber = 'DE123456789';

        $this->priceManager->calculatePrice($dto);
    }

    public function testCalculatePriceCouponNotFound(): void
    {
        $product = new Product();
        $product->setPrice('100.00');

        $this->productRepository
            ->find(1)
            ->willReturn($product);

        $this->couponRepository
            ->findOneBy(['code' => 'UNKNOWN'])
            ->willReturn(null);

        $this->expectException(BusinessValidationException::class);
        $this->expectExceptionMessage('Invalid coupon');
        $this->expectExceptionCode(0);

        $dto = new \App\DTO\CalculatePriceRequest();
        $dto->product = 1;
        $dto->taxNumber = 'DE123456789';
        $dto->couponCode = 'UNKNOWN';

        $this->priceManager->calculatePrice($dto);
    }

    public function testCalculatePriceSuccess(): void
    {
        $product = new Product();
        $product->setPrice('100.00');

        $coupon = new Coupon();
        $coupon->setType(\App\Enum\CouponTypeEnum::Fixed);
        $coupon->setValue('10.00');

        $this->productRepository
            ->find(1)
            ->willReturn($product);

        $this->couponRepository
            ->findOneBy(['code' => 'SALE6AMOUNT'])
            ->willReturn($coupon);

        $this->priceCalculator
            ->calculate($product, 'DE123456789', $coupon)
            ->willReturn('107.10');

        $dto = new \App\DTO\CalculatePriceRequest();
        $dto->product = 1;
        $dto->taxNumber = 'DE123456789';
        $dto->couponCode = 'SALE6AMOUNT';

        $result = $this->priceManager->calculatePrice($dto);

        self::assertSame('107.10', $result);
    }
}

<?php

namespace App\Tests\Service\Payment;

use App\Service\Payment\PaymentProcessorProvider;
use App\Service\Payment\PaymentProcessorInterface;
use PHPUnit\Framework\TestCase;

class PaymentProcessorProviderTest extends TestCase
{
    private PaymentProcessorProvider $factory;
    private PaymentProcessorInterface $paypalProcessor;
    private PaymentProcessorInterface $stripeProcessor;

    protected function setUp(): void
    {
        $this->paypalProcessor = new class implements PaymentProcessorInterface {
            public function pay(string $amount): void {}
            public static function getName(): string { return 'paypal'; }
        };

        $this->stripeProcessor = new class implements PaymentProcessorInterface {
            public function pay(string $amount): void {}
            public static function getName(): string { return 'stripe'; }
        };

        $this->factory = new PaymentProcessorProvider([
            $this->paypalProcessor,
            $this->stripeProcessor,
        ]);
    }

    public function testGetAvailableProcessors(): void
    {
        $processors = $this->factory->getAvailableProcessors();
        \sort($processors);
        self::assertEquals(['paypal', 'stripe'], $processors);
    }

    public function testGetProcessorSuccess(): void
    {
        $processor = $this->factory->getProcessor('paypal');
        self::assertSame($this->paypalProcessor, $processor);
    }

    public function testGetProcessorUnknown(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->factory->getProcessor('unknown');
    }
}

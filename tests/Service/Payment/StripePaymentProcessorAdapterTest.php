<?php

namespace App\Tests\Service\Payment;

use App\Service\Payment\StripePaymentProcessorAdapter;
use PHPUnit\Framework\TestCase;
use Systemeio\TestForCandidates\PaymentProcessor\StripePaymentProcessor;

class StripePaymentProcessorAdapterTest extends TestCase
{
    private StripePaymentProcessorAdapter $adapter;
    private StripePaymentProcessor $stripe;

    protected function setUp(): void
    {
        $this->stripe = $this->createMock(StripePaymentProcessor::class);
        $this->adapter = new StripePaymentProcessorAdapter();
    }

    public function testPaySuccess(): void
    {
        $this->stripe
            ->expects($this->once())
            ->method('processPayment')
            ->with(107.1)
            ->willReturn(true);

        $this->adapter->pay('107.10');
    }

    public function testPayFailure(): void
    {
        $this->stripe
            ->method('processPayment')
            ->with(107.1)
            ->willReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Stripe payment failed');

        $this->adapter->pay('107.10');
    }

    public function testGetName(): void
    {
        self::assertSame('stripe', StripePaymentProcessorAdapter::getName());
    }
}

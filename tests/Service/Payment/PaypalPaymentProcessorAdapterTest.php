<?php

namespace App\Tests\Service\Payment;

use App\Service\Payment\PaypalPaymentProcessorAdapter;
use PHPUnit\Framework\TestCase;
use Systemeio\TestForCandidates\PaymentProcessor\PaypalPaymentProcessor;

class PaypalPaymentProcessorAdapterTest extends TestCase
{
    private PaypalPaymentProcessorAdapter $adapter;
    private PaypalPaymentProcessor $paypal;

    protected function setUp(): void
    {
        $this->paypal = $this->createMock(PaypalPaymentProcessor::class);
        $this->adapter = new PaypalPaymentProcessorAdapter();
    }

    public function testPaySuccess(): void
    {
        $this->paypal
            ->expects($this->once())
            ->method('pay')
            ->with(10710); // 107.10 * 100

        $this->adapter->pay('107.10');
    }

    public function testGetName(): void
    {
        self::assertSame('paypal', PaypalPaymentProcessorAdapter::getName());
    }
}


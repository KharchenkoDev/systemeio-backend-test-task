<?php

namespace App\Tests\Service\Payment;

use App\Service\Payment\PaymentProcessorFactory;
use App\Service\Payment\PaymentProcessorInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\PhpUnit\ProphecyTrait;

class PaymentProcessorFactoryTest extends TestCase
{
    use ProphecyTrait;

    private PaymentProcessorFactory $factory;
    private ObjectProphecy $paypalProcessor;
    private ObjectProphecy $stripeProcessor;

    protected function setUp(): void
    {
        $this->paypalProcessor = $this->prophesize(PaymentProcessorInterface::class);
        $this->paypalProcessor::getName()->willReturn('paypal');

        $this->stripeProcessor = $this->prophesize(PaymentProcessorInterface::class);
        $this->stripeProcessor::getName()->willReturn('stripe');

        $this->factory = new PaymentProcessorFactory([
            $this->paypalProcessor->reveal(),
            $this->stripeProcessor->reveal(),
        ]);
    }

    public function testGetAvailableProcessors(): void
    {
        $processors = $this->factory->getAvailableProcessors();
        self::assertEquals(['paypal', 'stripe'], $processors);
    }

    public function testGetProcessorSuccess(): void
    {
        $processor = $this->factory->getProcessor('paypal');
        self::assertSame($this->paypalProcessor->reveal(), $processor);
    }

    public function testGetProcessorUnknown(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->factory->getProcessor('unknown');
    }
}

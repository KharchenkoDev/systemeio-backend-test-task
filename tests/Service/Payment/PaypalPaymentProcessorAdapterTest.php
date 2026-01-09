<?php

namespace App\Tests\Service\Payment;

use App\Service\Payment\PaypalPaymentProcessorAdapter;
use PHPUnit\Framework\TestCase;
use Systemeio\TestForCandidates\PaymentProcessor\PaypalPaymentProcessor;

class PaypalPaymentProcessorAdapterTest extends TestCase
{
    private PaypalPaymentProcessorAdapter $adapter;
    private \ReflectionProperty $processorProperty;

    protected function setUp(): void
    {
        $this->adapter = new PaypalPaymentProcessorAdapter();
        
        // Получаем доступ к приватному свойству $processor через рефлексию
        $reflection = new \ReflectionClass($this->adapter);
        $this->processorProperty = $reflection->getProperty('processor');
    }

    public function testPaySuccess(): void
    {
        $paypalMock = $this->createMock(PaypalPaymentProcessor::class);
        
        $paypalMock->expects($this->once())
            ->method('pay')
            ->with(10710); // 107.10 * 100
        
        // Подменяем реальный процессор на мок
        $this->processorProperty->setValue($this->adapter, $paypalMock);
        
        $this->adapter->pay('107.10');
    }

    public function testGetName(): void
    {
        self::assertSame('paypal', PaypalPaymentProcessorAdapter::getName());
    }
}

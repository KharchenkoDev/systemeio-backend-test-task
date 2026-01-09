<?php

namespace App\Tests\Service\Payment;

use App\Service\Payment\StripePaymentProcessorAdapter;
use PHPUnit\Framework\TestCase;
use Systemeio\TestForCandidates\PaymentProcessor\StripePaymentProcessor;

class StripePaymentProcessorAdapterTest extends TestCase
{
    private StripePaymentProcessorAdapter $adapter;
    private \ReflectionProperty $processorProperty;

    protected function setUp(): void
    {
        $this->adapter = new StripePaymentProcessorAdapter();
        
        // Получаем доступ к приватному свойству $processor через рефлексию
        $reflection = new \ReflectionClass($this->adapter);
        $this->processorProperty = $reflection->getProperty('processor');
    }

    public function testPaySuccess(): void
    {
        $stripeMock = $this->createMock(StripePaymentProcessor::class);
        
        $stripeMock->expects($this->once())
            ->method('processPayment')
            ->with(107.1)
            ->willReturn(true);
        
        // Подменяем реальный процессор на мок
        $this->processorProperty->setValue($this->adapter, $stripeMock);
        
        $this->adapter->pay('107.10');
    }

    public function testPayFailure(): void
    {
        // Создаем мок StripePaymentProcessor
        $stripeMock = $this->createMock(StripePaymentProcessor::class);
        
        $stripeMock->expects($this->once())
            ->method('processPayment')
            ->with(107.1)
            ->willReturn(false);
        
        // Подменяем реальный процессор на мок
        $this->processorProperty->setValue($this->adapter, $stripeMock);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Stripe payment failed');
        
        $this->adapter->pay('107.10');
    }

    public function testGetName(): void
    {
        self::assertSame('stripe', StripePaymentProcessorAdapter::getName());
    }
}

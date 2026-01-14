<?php

namespace App\Service\Payment;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class PaymentProcessorProvider
{
    private array $processors = [];

    public function __construct(
        #[AutowireIterator('app.payment_processor')] 
        iterable $processors
    ) {
        foreach ($processors as $processor) {
            $this->processors[$processor::getName()] = $processor;
        }
    }

    public function getProcessor(string $processorName): PaymentProcessorInterface
    {
        return $this->processors[$processorName] 
            ?? throw new \InvalidArgumentException("Unknown payment processor: $processorName");
    }

    public function getAvailableProcessors(): array
    {
        return array_keys($this->processors);
    }
}

<?php

namespace App\Service\Payment;

use Systemeio\TestForCandidates\PaymentProcessor\StripePaymentProcessor;

class StripePaymentProcessorAdapter implements PaymentProcessorInterface
{
    public function __construct(
        private StripePaymentProcessor $processor
    ) {}

    public function pay(string $amount): void
    {
        $floatAmount = (float) $amount;

        $success = $this->processor->processPayment($floatAmount);

        if (!$success) {
            throw new \Exception('Stripe payment failed');
        }
    }

    public static function getName(): string
    {
        return 'stripe';
    }
}

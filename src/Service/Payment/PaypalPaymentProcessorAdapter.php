<?php

namespace App\Service\Payment;

use Systemeio\TestForCandidates\PaymentProcessor\PaypalPaymentProcessor;

class PaypalPaymentProcessorAdapter implements PaymentProcessorInterface
{
    public function __construct(
        private PaypalPaymentProcessor $processor
    ) {}

    public function pay(string $amount): void
    {
        $amountInCents = (int)\bcmul($amount, '100');
        dump($amountInCents); // ! удалить
        $this->processor->pay($amountInCents);
    }

    public static function getName(): string
    {
        return 'paypal';
    }
}

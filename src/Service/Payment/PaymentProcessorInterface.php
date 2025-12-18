<?php

namespace App\Service\Payment;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.payment_processor')]
interface PaymentProcessorInterface
{
    /**
     * @param string $amount price as string (e.g. "100.50")
     * @throws \Exception when unsuccessful payment
     */
    public function pay(string $amount): void;

    /**
     * Payment Processor ID (e.g. "paypal")
     */
    public static function getName(): string;
}

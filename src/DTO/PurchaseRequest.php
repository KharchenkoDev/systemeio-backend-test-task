<?php

namespace App\DTO;

use App\Validator\ValidPaymentProcessor;
use Symfony\Component\Validator\Constraints as Assert;

class PurchaseRequest extends CalculatePriceRequest
{
  #[Assert\NotBlank]
  #[ValidPaymentProcessor]
  public string $paymentProcessor;
}

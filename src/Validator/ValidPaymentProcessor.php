<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ValidPaymentProcessor extends Constraint
{
    public string $message = 'The payment processor "{{ value }}" is not valid. Available processors: {{ available_processors }}.';
}

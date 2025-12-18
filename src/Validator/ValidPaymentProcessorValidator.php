<?php

namespace App\Validator;

use App\Service\Payment\PaymentProcessorFactory;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ValidPaymentProcessorValidator extends ConstraintValidator
{
    public function __construct(
        private PaymentProcessorFactory $paymentProcessorFactory
    ) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidPaymentProcessor) {
            throw new UnexpectedTypeException($constraint, ValidPaymentProcessor::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $availableProcessors = $this->paymentProcessorFactory->getAvailableProcessors();

        if (!in_array($value, $availableProcessors, true)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->setParameter('{{ available_processors }}', implode(', ', $availableProcessors))
                ->addViolation();
        }
    }
}

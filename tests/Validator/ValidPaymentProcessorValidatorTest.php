<?php

// tests/Validator/ValidPaymentProcessorValidatorTest.php
namespace App\Tests\Validator;

use App\Service\Payment\PaymentProcessorFactory;
use App\Validator\ValidPaymentProcessor;
use App\Validator\ValidPaymentProcessorValidator;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class ValidPaymentProcessorValidatorTest extends TestCase
{
    use ProphecyTrait;

    private ValidPaymentProcessorValidator $validator;
    private ObjectProphecy $factory;
    private ObjectProphecy $context;
    private ObjectProphecy $violationBuilder;

    protected function setUp(): void
    {
        $this->factory = $this->prophesize(PaymentProcessorFactory::class);
        $this->context = $this->prophesize(ExecutionContextInterface::class);
        $this->violationBuilder = $this->prophesize(ConstraintViolationBuilderInterface::class);

        $this->validator = new ValidPaymentProcessorValidator($this->factory->reveal());
        $this->validator->initialize($this->context->reveal());
    }

    public function testNullValueIsValid(): void
    {
        $constraint = new ValidPaymentProcessor();

        $this->context
            ->buildViolation($constraint->message)
            ->shouldNotBeCalled();

        $this->validator->validate(null, $constraint);
    }

    public function testEmptyStringValueIsValid(): void
    {
        $constraint = new ValidPaymentProcessor();

        $this->context
            ->buildViolation($constraint->message)
            ->shouldNotBeCalled();

        $this->validator->validate('', $constraint);
    }

    public function testValidProcessor(): void
    {
        $constraint = new ValidPaymentProcessor();

        $this->factory
            ->getAvailableProcessors()
            ->willReturn(['paypal', 'stripe']);

        $this->context
            ->buildViolation($constraint->message)
            ->shouldNotBeCalled();

        $this->validator->validate('paypal', $constraint);
        $this->validator->validate('stripe', $constraint);
    }

    public function testInvalidProcessor(): void
    {
        $constraint = new ValidPaymentProcessor();

        $this->factory
            ->getAvailableProcessors()
            ->willReturn(['paypal', 'stripe']);

        $this->context
            ->buildViolation($constraint->message)
            ->withArguments([
                '{{ value }}' => 'unknown',
                '{{ available_processors }}' => 'paypal, stripe',
            ])
            ->willReturn($this->violationBuilder->reveal())
            ->shouldBeCalledOnce();

        $this->validator->validate('unknown', $constraint);
    }

    public function testUnexpectedConstraintType(): void
    {
        $this->expectException(\Symfony\Component\Validator\Exception\UnexpectedTypeException::class);

        $this->validator->validate('paypal', $this->createMock(Constraint::class));
    }

    public function testUnexpectedConstraintClass(): void
    {
        $this->expectException(\Symfony\Component\Validator\Exception\UnexpectedTypeException::class);

        $this->validator->validate('paypal', new class extends Constraint {
            public function validatedBy(): string
            {
                return 'App\Validator\ValidPaymentProcessorValidator';
            }
        });
    }
}

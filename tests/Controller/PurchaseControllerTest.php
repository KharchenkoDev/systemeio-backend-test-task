<?php

// tests/Controller/PurchaseControllerTest.php
namespace App\Tests\Controller;

use App\Controller\PurchaseController;
use App\Service\Payment\PaymentProcessorFactory;
use App\Service\PriceManager;
use App\Service\RequestDtoResolver;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PurchaseControllerTest extends WebTestCase
{
    public function testPurchaseSuccess(): void
    {
        $client = static::createClient();

        $priceManager = $this->createStub(PriceManager::class);
        $priceManager
            ->method('calculatePrice')
            ->willReturn('107.10');

        $processor = $this->createMock(\App\Service\Payment\PaymentProcessorInterface::class);
        $processor
            ->expects($this->once())
            ->method('pay')
            ->with('107.10');

        $factory = $this->createStub(PaymentProcessorFactory::class);
        $factory
            ->method('getProcessor')
            ->with('paypal')
            ->willReturn($processor);

        $resolver = $this->createStub(RequestDtoResolver::class);
        $resolver
            ->method('resolve')
            ->willReturnCallback(function (Request $request, string $dtoClass) {
                $dto = new \App\DTO\PurchaseRequest();
                $dto->product = 1;
                $dto->taxNumber = 'DE123456789';
                $dto->couponCode = 'SALE6AMOUNT';
                $dto->paymentProcessor = 'paypal';
                return $dto;
            });

        $client->getContainer()->set(PriceManager::class, $priceManager);
        $client->getContainer()->set(PaymentProcessorFactory::class, $factory);
        $client->getContainer()->set(RequestDtoResolver::class, $resolver);

        $client->request(
            'POST',
            '/purchase',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"product":1,"taxNumber":"DE123456789","couponCode":"SALE6AMOUNT","paymentProcessor":"paypal"}'
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('status', $data);
        self::assertSame('success', $data['status']);
        self::assertArrayHasKey('price', $data);
        self::assertSame(107.1, $data['price']);
    }

    public function testPurchaseValidationFailed(): void
    {
        $client = static::createClient();

        $resolver = $this->createStub(RequestDtoResolver::class);
        $resolver
            ->method('resolve')
            ->willThrowException(new \Symfony\Component\HttpFoundation\Exception\BadRequestException(
                json_encode(['errors' => [['field' => 'product', 'message' => 'This value should be positive.']]])
            ));

        $client->getContainer()->set(RequestDtoResolver::class, $resolver);

        $client->request(
            'POST',
            '/purchase',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"product":0,"taxNumber":"DE123456789","paymentProcessor":"paypal"}'
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        self::assertArrayHasKey('errors', $data);
        self::assertCount(1, $data['errors']);
        self::assertSame('product', $data['errors'][0]['field']);
        self::assertSame('This value should be positive.', $data['errors'][0]['message']);
    }

    public function testPurchaseBusinessValidationFailed(): void
    {
        $client = static::createClient();

        $priceManager = $this->createStub(PriceManager::class);
        $priceManager
            ->method('calculatePrice')
            ->willThrowException(new \App\Exception\BusinessValidationException('product', 'Product not found'));

        $resolver = $this->createStub(RequestDtoResolver::class);
        $resolver
            ->method('resolve')
            ->willReturnCallback(function (Request $request, string $dtoClass) {
                $dto = new \App\DTO\PurchaseRequest();
                $dto->product = 999;
                $dto->taxNumber = 'DE123456789';
                $dto->paymentProcessor = 'paypal';
                return $dto;
            });

        $client->getContainer()->set(PriceManager::class, $priceManager);
        $client->getContainer()->set(RequestDtoResolver::class, $resolver);

        $client->request(
            'POST',
            '/purchase',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"product":999,"taxNumber":"DE123456789","paymentProcessor":"paypal"}'
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);

        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        self::assertArrayHasKey('errors', $data);
        self::assertCount(1, $data['errors']);
        self::assertSame('product', $data['errors'][0]['field']);
        self::assertSame('Product not found', $data['errors'][0]['message']);
    }

    public function testPurchasePaymentProcessorUnknown(): void
    {
        $client = static::createClient();

        $priceManager = $this->createStub(PriceManager::class);
        $priceManager
            ->method('calculatePrice')
            ->willReturn('107.10');

        $factory = $this->createStub(PaymentProcessorFactory::class);
        $factory
            ->method('getProcessor')
            ->with('unknown')
            ->willThrowException(new \InvalidArgumentException('Unknown payment processor: unknown'));

        $resolver = $this->createStub(RequestDtoResolver::class);
        $resolver
            ->method('resolve')
            ->willReturnCallback(function (Request $request, string $dtoClass) {
                $dto = new \App\DTO\PurchaseRequest();
                $dto->product = 1;
                $dto->taxNumber = 'DE123456789';
                $dto->paymentProcessor = 'unknown';
                return $dto;
            });

        $client->getContainer()->set(PriceManager::class, $priceManager);
        $client->getContainer()->set(PaymentProcessorFactory::class, $factory);
        $client->getContainer()->set(RequestDtoResolver::class, $resolver);

        $client->request(
            'POST',
            '/purchase',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"product":1,"taxNumber":"DE123456789","paymentProcessor":"unknown"}'
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('errors', $data);
        self::assertCount(1, $data['errors']);
        self::assertStringContainsString('Unknown payment processor', $data['errors'][0]['message']);
    }

    public function testPurchasePaymentFailed(): void
    {
        $client = static::createClient();

        $priceManager = $this->createStub(PriceManager::class);
        $priceManager
            ->method('calculatePrice')
            ->willReturn('107.10');

        $processor = $this->createStub(\App\Service\Payment\PaymentProcessorInterface::class);
        $processor
            ->method('pay')
            ->willThrowException(new \Exception('Payment failed'));

        $factory = $this->createStub(PaymentProcessorFactory::class);
        $factory
            ->method('getProcessor')
            ->with('paypal')
            ->willReturn($processor);

        $resolver = $this->createStub(RequestDtoResolver::class);
        $resolver
            ->method('resolve')
            ->willReturnCallback(function (Request $request, string $dtoClass) {
                $dto = new \App\DTO\PurchaseRequest();
                $dto->product = 1;
                $dto->taxNumber = 'DE123456789';
                $dto->paymentProcessor = 'paypal';
                return $dto;
            });

        $client->getContainer()->set(PriceManager::class, $priceManager);
        $client->getContainer()->set(PaymentProcessorFactory::class, $factory);
        $client->getContainer()->set(RequestDtoResolver::class, $resolver);

        $client->request(
            'POST',
            '/purchase',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"product":1,"taxNumber":"DE123456789","paymentProcessor":"paypal"}'
        );

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('errors', $data);
        self::assertCount(1, $data['errors']);
        self::assertStringContainsString('Payment failed', $data['errors'][0]['message']);
    }
}

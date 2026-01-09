<?php

namespace App\Tests\Controller;

use App\Controller\CalculatePriceController;
use App\Service\PriceManager;
use App\Service\RequestDtoResolver;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CalculatePriceControllerTest extends WebTestCase
{
    public function testCalculatePriceSuccess(): void
    {
        $client = static::createClient();

        $priceManager = $this->createStub(PriceManager::class);
        $priceManager
            ->method('calculatePrice')
            ->willReturn('107.10');

        $resolver = $this->createStub(RequestDtoResolver::class);
        $resolver
            ->method('resolve')
            ->willReturnCallback(function (Request $request, string $dtoClass) {
                $dto = new \App\DTO\CalculatePriceRequest();
                $dto->product = 1;
                $dto->taxNumber = 'DE123456789';
                $dto->couponCode = 'SALE6AMOUNT';
                return $dto;
            });

        $client->getContainer()->set(PriceManager::class, $priceManager);
        $client->getContainer()->set(RequestDtoResolver::class, $resolver);

        $client->request(
            'POST',
            '/calculate-price',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"product":1,"taxNumber":"DE123456789","couponCode":"SALE6AMOUNT"}'
        );

        $response = $client->getResponse();
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            '{"price":107.1}',
            $response->getContent()
        );
    }

    public function testCalculatePriceValidationFailed(): void
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
            '/calculate-price',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{"product":0,"taxNumber":"DE123456789"}'
        );

        $response = $client->getResponse();
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            '{"errors":[{"field":"product","message":"This value should be positive."}]}',
            $response->getContent()
        );
    }
}

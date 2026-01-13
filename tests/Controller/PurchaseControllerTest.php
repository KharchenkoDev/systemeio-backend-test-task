<?php

namespace App\Tests\Controller;

use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PurchaseControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
    }

    private function getProductIdByName(string $name): int
    {
        $product = $this->entityManager->getRepository(Product::class)->findOneBy(['name' => $name]);
        if (!$product) {
            $this->fail("Product '$name' not found.");
        }
        return $product->getId();
    }

    public function testPurchasePaypalSuccess(): void
    {
        $productId = $this->getProductIdByName('Наушники');

        $this->client->request('POST', '/purchase', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'product' => $productId,
            'taxNumber' => 'IT12345678901', // Италия 22%
            'couponCode' => 'SALE10AMOUNT',
            'paymentProcessor' => 'paypal'
        ]));

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($response['success']);
        // Наушники 20 - 10 (купон) = 10. + 22% налог = 12.20
        $this->assertEquals(12.20, $response['price']);
    }

    public function testPurchaseStripeSuccess(): void
    {
        $productId = $this->getProductIdByName('Iphone');

        $this->client->request('POST', '/purchase', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'product' => $productId,
            'taxNumber' => 'GR123456789', // Греция 24%
            'paymentProcessor' => 'stripe'
        ]));

        $this->assertResponseIsSuccessful();
    }
}

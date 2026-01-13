<?php

namespace App\Tests\Controller;

use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CalculatePriceControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
    }

    /**
     * Helper для получения ID продукта по названию (так как ID могут меняться)
     */
    private function getProductIdByName(string $name): int
    {
        $product = $this->entityManager->getRepository(Product::class)->findOneBy(['name' => $name]);
        
        if (!$product) {
            $this->fail("Product '$name' not found in fixtures.");
        }
        
        return $product->getId();
    }

    public function testCalculatePriceSuccess(): void
    {
        $iphoneId = $this->getProductIdByName('Iphone');

        $this->client->request('POST', '/calculate-price', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'product' => $iphoneId,
            'taxNumber' => 'DE123456789',
            'couponCode' => 'SALE15PERCENT'
        ]));

        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('price', $response);
        $this->assertIsFloat($response['price']);
    }
}

<?php

namespace App\Controller;

use App\DTO\CalculatePriceRequest;
use App\Exception\BusinessValidationException;
use App\Service\PriceManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class CalculatePriceController extends AbstractController
{
    public function __construct(
        private PriceManager $priceManager
    ) {}

    #[Route('/calculate-price', name: 'calculate_price', methods: ['POST'])]
    public function index(
        #[MapRequestPayload] CalculatePriceRequest $dto
    ): JsonResponse {
        try {
            $price = $this->priceManager->calculatePrice($dto);
            
            return $this->json([
                'success' => true,
                'price' => (float)$price
            ]);

        } catch (BusinessValidationException $e) {
            return $this->json(
                ['errors' => [['field' => $e->getField(), 'message' => $e->getMessage()]]],
                422
            );
        } catch (\Exception $e) {
            return $this->json(['errors' => [['message' => $e->getMessage()]]], 400);
        }
    }
}

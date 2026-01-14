<?php

namespace App\Controller;

use App\DTO\PurchaseRequest;
use App\Exception\BusinessValidationException;
use App\Service\Payment\PaymentProcessorProvider;
use App\Service\PriceManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PurchaseController extends AbstractController
{
    public function __construct(
        private PriceManager $priceManager,
        private PaymentProcessorProvider $PaymentProcessorProvider,
    ) {}

    #[Route('/purchase', name: 'purchase', methods: ['POST'])]
    public function index(
        #[MapRequestPayload] PurchaseRequest $dto
    ): JsonResponse {
        try {
            $price = $this->priceManager->calculatePrice($dto);

            $processor = $this->PaymentProcessorProvider->getProcessor($dto->paymentProcessor);
            $processor->pay($price);

            return $this->json([
                'success' => true,
                'price' => (float) $price,
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

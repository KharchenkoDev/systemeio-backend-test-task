<?php

namespace App\Controller;

use App\DTO\CalculatePriceRequest;
use App\Repository\CouponRepository;
use App\Repository\ProductRepository;
use App\Service\PriceCalculator;
use App\Service\RequestDtoResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class CalculatePriceController extends AbstractController
{
    public function __construct(
        private ProductRepository $productRepository,
        private CouponRepository $couponRepository,
        private PriceCalculator $priceCalculator,
    ) {}

    #[Route('/calculate-price', name: 'calculate_price', methods: ['POST'])]
    public function index(
        Request $request,
        RequestDtoResolver $resolver
    ): JsonResponse {
        try {
            /** @var CalculatePriceRequest $dto */
            $dto = $resolver->resolve($request, CalculatePriceRequest::class);
        } catch (BadRequestException $e) {
            return new JsonResponse(
                json_decode($e->getMessage(), true),
                422
            );
        }

        $product = $this->productRepository->find($dto->product);

        if (null === $product) {
            return $this->json(
                ['errors' => [['field' => 'product', 'message' => 'Product not found']]],
                422
            );
        }

        $coupon = null;

        if (null !== $dto->couponCode) {
            $coupon = $this->couponRepository->findOneBy([
                'code' => $dto->couponCode,
            ]);

            if (!$coupon) {
                return $this->json(
                    ['errors' => [['field' => 'couponCode', 'message' => 'Invalid coupon']]],
                    422
                );
            }
        }

        $price = $this->priceCalculator->calculate(
            $product,
            $dto->taxNumber,
            $coupon
        );

        return $this->json(['price' => $price]);
    }
}

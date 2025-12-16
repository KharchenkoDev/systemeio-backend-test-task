<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RequestDtoResolver
{
    public function __construct(
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {}

    public function resolve(Request $request, string $dtoClass): object
    {
        try {
            $dto = $this->serializer->deserialize(
                $request->getContent(),
                $dtoClass,
                'json'
            );
        } catch (\Throwable) {
            throw new BadRequestException(
                json_encode([
                    'errors' => [
                        ['message' => 'Invalid JSON body'],
                    ],
                ])
            );
        }

        $violations = $this->validator->validate($dto);

        if ($violations->count() > 0) {
            $errors = [];

            foreach ($violations as $violation) {
                $errors[] = [
                    'field' => $violation->getPropertyPath(),
                    'message' => $violation->getMessage(),
                ];
            }

            /**
             * Упрощённая обработка ошибок, чтобы не городить кастомный Exception в тестовом задании.
             */
            throw new BadRequestException(
                json_encode(['errors' => $errors])
            );
        }

        return $dto;
    }
}

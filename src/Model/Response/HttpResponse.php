<?php

namespace App\Model\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

class HttpResponse
{
    public function __construct(
        public readonly int $status,
        public readonly mixed $data = null
    ) {}

    public function toJson(): JsonResponse
    {
        return new JsonResponse($this->data, $this->status);
    }
}

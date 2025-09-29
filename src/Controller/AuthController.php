<?php

namespace App\Controller;

use App\Model\Request\LoginRequest;
use App\Model\Request\RegisterRequest;
use App\Service\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/user')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly AuthService $auth,
    ) {}

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(#[MapRequestPayload] RegisterRequest $req): JsonResponse
    {
        return $this->json($this->auth->register($req), 201);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(#[MapRequestPayload] LoginRequest $req): JsonResponse
    {
        return $this->json($this->auth->login($req), 201);
    }
}

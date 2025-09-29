<?php

namespace App\Service\Impl;

use App\Entity\User;
use App\Model\Request\LoginRequest;
use App\Model\Request\RegisterRequest;
use App\Model\Response\HttpResponse;
use App\Repository\UserRepository;
use App\Service\AuthService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthServiceImpl implements AuthService
{

    public function __construct(
        private readonly UserRepository $users,
        private readonly EntityManagerInterface  $em,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly JWTTokenManagerInterface $jwt,
    ) {}

    public function register(RegisterRequest $request)
    {
        if ($this->users->findOneBy(['login' => $request->login])) {
            return new HttpResponse(
                400,
                [
                    "error" => "login taken"
                ]
            );
        }

        if ($request->password != $request->passwordConfirm) {
            return new HttpResponse(
                400,
                [
                    "error" => "passwords are different"
                ]
            );
        }

        $user = new User();
        $user->setName($request->name);
        $user->setLogin($request->login);

        if (method_exists($user, 'setRoles')) {
            $user->setRoles(['ROLE_USER']);
        }

        $hash = $this->hasher->hashPassword($user, $request->password);
        $user->setPassword($hash);

        $this->em->persist($user);
        $this->em->flush();


        return new HttpResponse(
        201,
        [
            'token' => $this->issueToken($user)
        ]
    );
    }

    public function login(LoginRequest $request)
    {
        $user = $this->users->findOneBy(['login' => $request->login]);
        if (!$user) {
            return new HttpResponse(
                400,
                [
                    "error" => "user not found"
                ]
            );
        }

        if (!$this->hasher->isPasswordValid($user, $request->password)) {
            return new HttpResponse(
                400,
                [
                    "error" => "password incorrect"
                ]
            );
        };

        return new HttpResponse(
            200,
            [
                'token' => $this->issueToken($user)
            ]
        );
    }

    public function me()
    {
        // TODO: Implement me() method.
    }

    public function issueToken(User $user): string
    {
        return $this->jwt->create($user);
    }
}

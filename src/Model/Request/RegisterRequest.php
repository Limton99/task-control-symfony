<?php

namespace App\Model\Request;

use Symfony\Component\Validator\Constraints as Assert;


class RegisterRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    public string $name;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    public string $login;

    #[Assert\NotBlank]
    #[Assert\Length(min: 6, max: 255)]
    public string $password;

    #[Assert\NotBlank]
    #[Assert\EqualTo(propertyPath: 'password', message: 'Passwords do not match')]
    public string $passwordConfirm;
}

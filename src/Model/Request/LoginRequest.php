<?php

namespace App\Model\Request;

use Symfony\Component\Validator\Constraints as Assert;

class LoginRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    public string $login;

    #[Assert\NotBlank]
    #[Assert\Length(min: 6, max: 255)]
    public string $password;
}

<?php

namespace App\Model\Request;

use Symfony\Component\Validator\Constraints as Assert;

class TaskRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    public string $title;

     #[Assert\NotBlank]
     #[Assert\Length(min: 2, max: 255)]
     public string $status;
}

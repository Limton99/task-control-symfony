<?php

namespace App\Service;

use App\Model\Request\LoginRequest;
use App\Model\Request\RegisterRequest;

interface AuthService
{
    public function register(RegisterRequest $request);
    public function login(LoginRequest $request);
    public function me();
}

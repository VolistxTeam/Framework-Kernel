<?php

namespace Volistx\FrameworkKernel\Helpers;

class PersonalTokensCenter
{
    private $token;

    public function setToken($token)
    {
        $this->token = $token;
    }

    public function getToken()
    {
        return $this->token;
    }
}

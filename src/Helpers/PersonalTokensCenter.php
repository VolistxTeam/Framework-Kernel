<?php

namespace Volistx\FrameworkKernel\Helpers;

class PersonalTokensCenter
{
    private mixed $token = null;

    /**
     * Set the personal token.
     *
     * @param mixed $token The personal token
     *
     * @return void
     */
    public function setToken(mixed $token): void
    {
        $this->token = $token;
    }

    /**
     * Get the personal token.
     *
     * @return mixed The personal token
     */
    public function getToken(): mixed
    {
        return $this->token;
    }
}

<?php

namespace Volistx\FrameworkKernel\DataTransferObjects;

use Illuminate\Support\Facades\Crypt;

class AdminLogDTO extends DataTransferObjectBase
{
    public string $access_token_id;
    public string $id;
    public string $url;
    public string $ip;
    public string $method;
    public ?string $user_agent;
    public string $created_at;

    public static function fromModel($userLog): self
    {
        return new self($userLog);
    }

    public function GetDTO(): array
    {
        return [
            'id'           => $this->id,
            'access_token' => [
                'id' => $this->access_token_id,
            ],
            'url' => Crypt::decryptString($this->url),
            'ip' => Crypt::decryptString($this->ip),
            'method' => Crypt::decryptString($this->method),
            'user_agent' => $this->user_agent,
            'created_at' => $this->created_at,
        ];
    }
}

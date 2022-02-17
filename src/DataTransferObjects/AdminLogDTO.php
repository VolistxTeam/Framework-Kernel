<?php

namespace Volistx\FrameworkKernel\DataTransferObjects;

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
            'url'        => $this->url,
            'ip'         => $this->ip,
            'method'     => $this->method,
            'user_agent' => $this->user_agent,
        ];
    }
}

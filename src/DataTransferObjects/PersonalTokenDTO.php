<?php

namespace Volistx\FrameworkKernel\DataTransferObjects;

use Carbon\Carbon;
use Volistx\FrameworkKernel\Enums\AccessRule;
use Volistx\FrameworkKernel\Enums\RateLimitMode;

class PersonalTokenDTO extends DataTransferObjectBase
{
    public string $id;
    public string $subscription_id;
    public int $rate_limit_mode;
    public array $permissions;
    public int $ip_rule;
    public array $ip_range;
    public int $country_rule;
    public array $country_range;
    public string $activated_at;
    public ?string $expires_at;
    public string $created_at;
    public string $updated_at;

    public static function fromModel($personal_token): self
    {
        return new self($personal_token);
    }

    public function GetDTO($key = null): array
    {
        $result = [
            'id'              => $this->id,
            'key'             => $key,
            'subscription'    => SubscriptionDTO::fromModel($this->entity->subscription()->first())->GetDTO(),
            'permissions'     => $this->permissions,
            'rate_limit_mode' => RateLimitMode::from($this->rate_limit_mode),
            'geolocation'     => [
                'ip_rule'       => AccessRule::from($this->ip_rule)->name,
                'ip_range'      => $this->ip_range,
                'country_rule'  => AccessRule::from($this->country_rule)->name,
                'country_range' => $this->country_range,
            ],
            'token_status' => [
                'is_expired'   => $this->expires_at != null && Carbon::now()->greaterThan(Carbon::createFromTimeString($this->expires_at)),
                'activated_at' => $this->activated_at,
                'expires_at'   => $this->expires_at,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        if (!$key) {
            unset($result['key']);
        }

        return $result;
    }
}

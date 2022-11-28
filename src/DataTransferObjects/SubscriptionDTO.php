<?php

namespace Volistx\FrameworkKernel\DataTransferObjects;

use Volistx\FrameworkKernel\Enums\SubscriptionStatus;

class SubscriptionDTO extends DataTransferObjectBase
{
    public string $id;
    public int $user_id;
    public string $activated_at;
    public ?string $expires_at;
    public string $created_at;
    public string $updated_at;
    public ?string $cancels_at;
    public ?string $cancelled_at;
    public ?int $status;

    public static function fromModel($subscription): self
    {
        return new self($subscription);
    }

    public function GetDTO(): array
    {
        return [
            'id'                 => $this->id,
            'user_id'            => $this->user_id,
            'plan'               => PlanDTO::fromModel($this->entity->plan()->first())->GetDTO(),
            'status' => [
                'status'       => SubscriptionStatus::from($this->status)->name,
                'activated_at' => $this->activated_at,
                'expires_at'   => $this->expires_at,
                'cancels_at'   => $this->cancels_at,
                'cancelled_at' => $this->cancelled_at,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

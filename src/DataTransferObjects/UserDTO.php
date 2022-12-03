<?php

namespace Volistx\FrameworkKernel\DataTransferObjects;

class UserDTO extends DataTransferObjectBase
{
    public string $id;
    public bool $is_active;

    public static function fromModel($plan): self
    {
        return new self($plan);
    }

    public function GetDTO(): array
    {
        return [
            'id'                => $this->id,
            'is_active'         => $this->is_active,
        ];
    }
}

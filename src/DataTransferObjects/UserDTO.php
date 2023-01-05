<?php

namespace Volistx\FrameworkKernel\DataTransferObjects;

class UserDTO extends DataTransferObjectBase
{
    public string $id;
    public bool $is_active;

    public static function fromModel($user): self
    {
        return new self($user);
    }

    public function GetDTO(): array
    {
        return [
            'id'                => $this->id,
            'is_active'         => $this->is_active,
        ];
    }
}

<?php

namespace App\Enums;

enum RoleName: string
{
    case SuperAdmin = 'Super Admin';

    public function label(): string
    {
        return $this->value;
    }
}

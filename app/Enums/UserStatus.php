<?php

namespace App\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    public static function labels(): array
    {
        return [
            self::Active->value => 'Active',
            self::Inactive->value => 'Inactive',
        ];
    }
}
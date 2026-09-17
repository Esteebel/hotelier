<?php

namespace App\Enums;

enum HotelStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::Active->value => 'Active',
            self::Inactive->value => 'Inactive',
        ];
    }
}
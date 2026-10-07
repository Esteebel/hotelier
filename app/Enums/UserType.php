<?php

namespace App\Enums;

enum UserType: string
{
    case Staff = 'staff';
    case Customer = 'customer';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::Staff->value => 'Staff',
            self::Customer->value => 'Customer',
        ];
    }
}

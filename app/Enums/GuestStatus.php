<?php

namespace App\Enums;

enum GuestStatus: string
{
    case Active = 'active';
    case Former = 'former';
    case Blacklisted = 'blacklisted';
    case Archived = 'archived';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::Active->value => 'Active',
            self::Former->value => 'Former',
            self::Blacklisted->value => 'Blacklisted',
            self::Archived->value => 'Archived',
        ];
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            self::Former => 'bg-gray-50 text-gray-700 ring-gray-600/20',
            self::Blacklisted => 'bg-red-50 text-red-700 ring-red-600/20',
            self::Archived => 'bg-amber-50 text-amber-700 ring-amber-600/20',
        };
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}

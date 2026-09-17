<?php

namespace App\Enums;

enum RoomStatus: string
{
    case Available = 'available';
    case Occupied = 'occupied';
    case Reserved = 'reserved';
    case Dirty = 'dirty';
    case Cleaning = 'cleaning';
    case Maintenance = 'maintenance';
    case OutOfService = 'out_of_service';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::Available->value => 'Available',
            self::Occupied->value => 'Occupied',
            self::Reserved->value => 'Reserved',
            self::Dirty->value => 'Dirty',
            self::Cleaning->value => 'Cleaning',
            self::Maintenance->value => 'Maintenance',
            self::OutOfService->value => 'Out of Service',
        ];
    }

    /**
     * Tailwind class map for status badges.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Available => 'bg-emerald-100 text-emerald-700',
            self::Occupied => 'bg-rose-100 text-rose-700',
            self::Reserved => 'bg-amber-100 text-amber-700',
            self::Dirty => 'bg-orange-100 text-orange-700',
            self::Cleaning => 'bg-blue-100 text-blue-700',
            self::Maintenance => 'bg-purple-100 text-purple-700',
            self::OutOfService => 'bg-gray-100 text-gray-500',
        };
    }
}
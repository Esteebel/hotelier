<?php

namespace App\Enums;

enum ReservationStatus: string
{
    case Confirmed = 'confirmed';
    case CheckedIn = 'checked_in';
    case Extended = 'extended';
    case CheckedOut = 'checked_out';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function badgeClass(): string
    {
        return match ($this) {
            self::Confirmed => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            self::CheckedIn => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
            self::Extended => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            self::CheckedOut => 'bg-gray-50 text-gray-700 ring-gray-600/20',
            self::Cancelled => 'bg-red-50 text-red-700 ring-red-600/20',
            self::NoShow => 'bg-rose-50 text-rose-700 ring-rose-600/20',
        };
    }

    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }
}

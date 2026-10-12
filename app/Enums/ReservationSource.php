<?php

namespace App\Enums;

enum ReservationSource: string
{
    case Direct = 'direct';
    case Phone = 'phone';
    case Website = 'website';
    case WalkIn = 'walk_in';
    case Email = 'email';
    case Referral = 'referral';
    case Ota = 'ota';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::Direct->value => 'Direct',
            self::Phone->value => 'Phone',
            self::Website->value => 'Website',
            self::WalkIn->value => 'Walk-in',
            self::Email->value => 'Email',
            self::Referral->value => 'Referral',
            self::Ota->value => 'OTA / Booking site',
        ];
    }

    public function label(): string
    {
        return self::labels()[$this->value] ?? ucfirst(str_replace('_', ' ', $this->value));
    }
}

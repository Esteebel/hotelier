<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case Void = 'void';

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            self::Completed => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            self::Failed, self::Void => 'bg-red-50 text-red-700 ring-red-600/20',
            self::Refunded => 'bg-gray-50 text-gray-700 ring-gray-600/20',
        };
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}

<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Overdue = 'overdue';
    case Void = 'void';

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'bg-gray-50 text-gray-700 ring-gray-600/20',
            self::Sent => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
            self::PartiallyPaid => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            self::Paid => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            self::Overdue => 'bg-red-50 text-red-700 ring-red-600/20',
            self::Void => 'bg-gray-100 text-gray-500 ring-gray-500/20',
        };
    }

    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }
}

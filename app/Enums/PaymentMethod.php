<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Card = 'card';
    case BankTransfer = 'bank_transfer';
    case MobileMoney = 'mobile_money';
    case Cheque = 'cheque';
    case CreditAccount = 'credit_account';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::Cash->value => 'Cash',
            self::Card->value => 'Card',
            self::BankTransfer->value => 'Bank Transfer',
            self::MobileMoney->value => 'Mobile Money',
            self::Cheque->value => 'Cheque',
            self::CreditAccount->value => 'Credit Account',
        ];
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Cash => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            self::Card => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
            self::BankTransfer, self::MobileMoney => 'bg-sky-50 text-sky-700 ring-sky-600/20',
            self::Cheque => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            self::CreditAccount => 'bg-purple-50 text-purple-700 ring-purple-600/20',
        };
    }

    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }
}

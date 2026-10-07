<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Support\ContextualBranch;
use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;
    use HasOrganizationScope;

    protected $fillable = [
        'branch_id',
        'reservation_id',
        'invoice_id',
        'guest_id',
        'payment_number',
        'method',
        'status',
        'amount',
        'reference',
        'paid_on',
        'notes',
        'received_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_on' => 'date',
        'method' => PaymentMethod::class,
        'status' => PaymentStatus::class,
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(HotelBranch::class, 'branch_id');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function getMethodLabelAttribute(): string
    {
        return PaymentMethod::labels()[$this->method->value] ?? ucfirst($this->method->value);
    }

    public function getStatusLabelAttribute(): string
    {
        return PaymentStatus::labels()[$this->status->value] ?? ucfirst($this->status->value);
    }

    /** Global scope keeps every payment under the resolved branch. */
    protected static function booted(): void
    {
        static::addGlobalScope('branch', function ($builder) {
            if (request()->filled('branch_id')) {
                return;
            }

            $builder->where('branch_id', ContextualBranch::id());
        });
    }
}

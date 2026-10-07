<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Support\ContextualBranch;
use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;
    use HasOrganizationScope;

    protected $fillable = [
        'branch_id',
        'reservation_id',
        'guest_id',
        'invoice_number',
        'issued_on',
        'due_on',
        'status',
        'subtotal',
        'tax',
        'discount',
        'total',
        'amount_paid',
        'line_items',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'issued_on' => 'date',
        'due_on' => 'date',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'status' => InvoiceStatus::class,
        'line_items' => 'array',
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

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return InvoiceStatus::labels()[$this->status->value] ?? ucfirst($this->status->value);
    }

    public function getBalanceDueAttribute(): float
    {
        return max(0, (float) $this->total - (float) $this->amount_paid);
    }

    /** Global scope keeps every invoice under the resolved branch. */
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

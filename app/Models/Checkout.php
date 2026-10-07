<?php

namespace App\Models;

use App\Support\ContextualBranch;
use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Checkout extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasOrganizationScope;

    protected $fillable = [
        'checkout_number',
        'branch_id',
        'reservation_id',
        'room_id',
        'guest_id',
        'processed_by',
        'checked_out_at',
        'total_charges',
        'total_paid',
        'balance_due',
        'status',
        'notes',
    ];

    protected $casts = [
        'checked_out_at' => 'datetime',
        'total_charges' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(HotelBranch::class, 'branch_id');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function scopeForBranch(Builder $query, ?int $branchId): Builder
    {
        return $query->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId));
    }

    public static function nextCheckoutNumber(): string
    {
        $branchCode = ContextualBranch::current()?->code ?? 'HQ';
        $count = static::withTrashed()->count() + 1;

        return $branchCode.'-CO-'.str_pad((string) $count, 7, '0', STR_PAD_LEFT);
    }
}

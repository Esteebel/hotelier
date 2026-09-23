<?php

namespace App\Models;

use App\Support\ContextualBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LaundryOrder extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'order_number',
        'branch_id',
        'room_id',
        'guest_id',
        'items',
        'total_amount',
        'status',
        'pickup_date',
        'delivery_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'items' => 'array',
        'total_amount' => 'decimal:2',
        'pickup_date' => 'date',
        'delivery_date' => 'date',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(HotelBranch::class, 'branch_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function scopeForBranch(Builder $query, ?int $branchId): Builder
    {
        return $query->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId));
    }

    public static function nextOrderNumber(): string
    {
        $branchCode = ContextualBranch::current()?->code ?? 'HQ';
        $count = static::withTrashed()->count() + 1;

        return $branchCode.'-LAU-'.str_pad((string) $count, 7, '0', STR_PAD_LEFT);
    }
}

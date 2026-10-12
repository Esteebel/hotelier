<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use App\Support\ContextualBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomTransfer extends Model
{
    use HasFactory;
    use HasOrganizationScope;
    use SoftDeletes;

    protected $fillable = [
        'transfer_number',
        'branch_id',
        'reservation_id',
        'from_room_id',
        'to_room_id',
        'transfer_date',
        'reason',
        'notes',
        'status',
        'requested_by',
        'handled_by',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(HotelBranch::class, 'branch_id');
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function fromRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'from_room_id');
    }

    public function toRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'to_room_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public static function nextTransferNumber(): string
    {
        $prefix = ContextualBranch::resolve()?->code ?? 'HQ';
        $next = (int) static::query()->withTrashed()->max('id') + 1;

        return $prefix.'-TR-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    public function scopeForBranch(Builder $query, ?int $branchId): Builder
    {
        return $branchId ? $query->where('branch_id', $branchId) : $query;
    }
}

<?php

namespace App\Models;

use App\Support\ContextualBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Checkin extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'task_number',
        'branch_id',
        'room_id',
        'assigned_to',
        'priority',
        'task_type',
        'due_date',
        'status',
        'notes',
        'requested_by',
        'handled_by',
        'handled_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'handled_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(HotelBranch::class, 'branch_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function scopeForBranch(Builder $query, ?int $branchId): Builder
    {
        return $query->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId));
    }

    public static function nextTaskNumber(): string
    {
        $branchCode = ContextualBranch::current()?->code ?? 'HQ';
        $count = static::withTrashed()->count() + 1;

        return $branchCode.'-HK-'.str_pad((string) $count, 7, '0', STR_PAD_LEFT);
    }
}

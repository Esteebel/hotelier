<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'notification_number',
        'branch_id',
        'user_id',
        'title',
        'message',
        'type',
        'status',
        'notifiable_type',
        'notifiable_id',
    ];

    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(HotelBranch::class, 'branch_id');
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function notifiable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForBranch(Builder $query, ?int $branchId): Builder
    {
        return $query->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId));
    }

    public static function nextNotificationNumber(): string
    {
        $count = static::withTrashed()->count() + 1;

        return 'NTF-'.str_pad((string) $count, 7, '0', STR_PAD_LEFT);
    }
}

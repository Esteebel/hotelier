<?php

namespace App\Models;

use App\Support\ContextualBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'staff_number',
        'branch_id',
        'first_name',
        'last_name',
        'position',
        'department',
        'email',
        'phone',
        'status',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(HotelBranch::class, 'branch_id');
    }

    public function getFullNameAttribute(): string
    {
        return $this->first_name.' '.$this->last_name;
    }

    public function scopeForBranch(Builder $query, ?int $branchId): Builder
    {
        return $query->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId));
    }

    public static function nextStaffNumber(): string
    {
        $branchCode = ContextualBranch::current()?->code ?? 'HQ';
        $count = static::withTrashed()->count() + 1;

        return $branchCode.'-STF-'.str_pad((string) $count, 7, '0', STR_PAD_LEFT);
    }
}

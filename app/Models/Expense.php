<?php

namespace App\Models;

use App\Support\ContextualBranch;
use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasOrganizationScope;

    protected $fillable = [
        'expense_number',
        'branch_id',
        'description',
        'amount',
        'category',
        'expense_date',
        'reference',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(HotelBranch::class, 'branch_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForBranch(Builder $query, ?int $branchId): Builder
    {
        return $query->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId));
    }

    public static function nextExpenseNumber(): string
    {
        $branchCode = ContextualBranch::current()?->code ?? 'HQ';
        $count = static::withTrashed()->count() + 1;

        return $branchCode.'-EXP-'.str_pad((string) $count, 7, '0', STR_PAD_LEFT);
    }
}

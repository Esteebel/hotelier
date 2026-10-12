<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use App\Models\Concerns\HasOrganizationScope;
use App\Support\ContextualBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends Model
{
    use HasOrganizationScope;
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'guest_id',
        'room_id',
        'reservation_number',
        'check_in',
        'check_out',
        'adults',
        'children',
        'status',
        'source',
        'rate_plan',
        'total_amount',
        'balance_due',
        'special_requests',
        'cancelled_at',
        'cancellation_reason',
        'created_by',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'adults' => 'integer',
        'children' => 'integer',
        'total_amount' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'cancelled_at' => 'date',
        'status' => ReservationStatus::class,
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(HotelBranch::class, 'branch_id');
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function invoice(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return ReservationStatus::labels()[$this->status->value] ?? ucfirst($this->status->value);
    }

    public function getNightsAttribute(): int
    {
        return $this->check_in->diffInDays($this->check_out);
    }

    /** Active (non-terminal) reservations for the branch context. */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [
            ReservationStatus::Cancelled->value,
            ReservationStatus::CheckedOut->value,
            ReservationStatus::NoShow->value,
        ]);
    }

    /** Global scope keeps every reservation under the resolved branch. */
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

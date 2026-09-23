<?php

namespace App\Models;

use App\Enums\GuestStatus;
use App\Enums\ReservationStatus;
use App\Support\ContextualBranch;
use Database\Factories\GuestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guest extends Model
{
    /** @use HasFactory<GuestFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'birth_date',
        'nationality',
        'id_number',
        'id_type',
        'address',
        'company',
        'notes',
        'is_vip',
        'status',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'is_vip' => 'boolean',
        'status' => GuestStatus::class,
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(HotelBranch::class, 'branch_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status
            ? (GuestStatus::labels()[$this->status->value] ?? ucfirst($this->status->value))
            : 'Active';
    }

    protected static function booted(): void
    {
        static::addGlobalScope('branchScoped', function ($builder) {
            $builder->where('branch_id', ContextualBranch::id());
        });
    }
}

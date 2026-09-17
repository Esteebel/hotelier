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
        'notes',
        'is_vip',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'is_vip' => 'boolean',
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

    public function getStatusAttribute(): string
    {
        $active = $this->reservations()
            ->where('branch_id', ContextualBranch::id())
            ->whereIn('status', [
                'confirmed',
                'checked_in',
                'extended',
            ])
            ->exists();

        return $active ? GuestStatus::Active->value : GuestStatus::Former->value;
    }

    public function getStatusLabelAttribute(): string
    {
        return GuestStatus::labels()[$this->status] ?? ucfirst($this->status);
    }

    protected static function booted(): void
    {
        static::addGlobalScope('branchScoped', function ($builder) {
            $builder->where('branch_id', ContextualBranch::id());
        });
    }
}

<?php

namespace App\Models;

use App\Enums\RoomStatus;
use App\Models\Concerns\HasOrganizationScope;
use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory, SoftDeletes;
    use HasOrganizationScope;

    protected $fillable = [
        'branch_id',
        'room_type_id',
        'room_number',
        'floor',
        'capacity',
        'price',
        'status',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'price' => 'decimal:2',
            'status' => RoomStatus::class,
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(HotelBranch::class, 'branch_id');
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class, 'room_type_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return RoomStatus::labels()[$this->status->value] ?? ucfirst($this->status->value);
    }
}
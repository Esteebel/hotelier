<?php

namespace App\Models;

use App\Enums\HotelStatus;
use Database\Factories\HotelBranchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HotelBranch extends Model
{
    /** @use HasFactory<HotelBranchFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'slug',
        'description',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'phone',
        'email',
        'currency',
        'star_rating',
        'check_in_time',
        'check_out_time',
        'status',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'star_rating' => 'integer',
            'is_default' => 'boolean',
            'status' => HotelStatus::class,
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(HotelOrganization::class, 'organization_id');
    }

    public function roomTypes(): HasMany
    {
        return $this->hasMany(RoomType::class, 'branch_id');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class, 'branch_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_branches', 'branch_id', 'user_id')
            ->withTimestamps();
    }

    public function getStatusLabelAttribute(): string
    {
        return HotelStatus::labels()[$this->status->value] ?? ucfirst($this->status->value);
    }
}
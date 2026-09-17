<?php

namespace App\Models;

use App\Enums\HotelStatus;
use Database\Factories\HotelOrganizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HotelOrganization extends Model
{
    /** @use HasFactory<HotelOrganizationFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'code',
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
    ];

    protected function casts(): array
    {
        return [
            'star_rating' => 'integer',
            'status' => HotelStatus::class,
        ];
    }

    public function branches(): HasMany
    {
        return $this->hasMany(HotelBranch::class, 'organization_id');
    }

    public function rooms(): HasMany
    {
        return $this->hasManyThrough(Room::class, HotelBranch::class, 'organization_id', 'branch_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return HotelStatus::labels()[$this->status->value] ?? ucfirst($this->status->value);
    }
}
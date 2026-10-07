<?php

namespace App\Models;


use App\Models\Concerns\HasOrganizationScope;
use Database\Factories\RoomTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomType extends Model
{
    /** @use HasFactory<RoomTypeFactory> */
    use HasFactory;
    use HasOrganizationScope;

    protected $fillable = [
        'branch_id',
        'name',
        'description',
        'base_price',
        'capacity',
        'number_of_beds',
        'bed_configuration',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'capacity' => 'integer',
            'number_of_beds' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(HotelBranch::class, 'branch_id');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
}
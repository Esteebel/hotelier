<?php

namespace App\Models;

use App\Enums\UserStatus;
use App\Enums\UserType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
        'user_type',
        'guest_id',
        'organization_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'user_type' => UserType::class,
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return UserStatus::labels()[$this->status->value] ?? ucfirst($this->status->value);
    }

    public function getTypeLabelAttribute(): string
    {
        return UserType::labels()[$this->user_type->value] ?? ucfirst($this->user_type->value);
    }

    public function isCustomer(): bool
    {
        return $this->user_type === UserType::Customer;
    }

    /**
     * Tenant-scoped users only ever see data from their own organization.
     * NULL organization = platform level (super admin).
     */
    public function isOrgScoped(): bool
    {
        return $this->organization_id !== null && ! $this->hasRole('super-admin');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(HotelOrganization::class, 'organization_id');
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class, 'guest_id')
            ->withoutGlobalScope('branchScoped');
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(HotelBranch::class, 'user_branches', 'user_id', 'branch_id')
            ->withTimestamps();
    }
}

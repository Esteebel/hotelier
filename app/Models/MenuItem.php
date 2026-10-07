<?php

namespace App\Models;

use App\Support\ContextualBranch;
use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuItem extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasOrganizationScope;

    protected $fillable = [
        'branch_id',
        'name',
        'category',
        'description',
        'price',
        'is_available',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_available' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(HotelBranch::class, 'branch_id');
    }

    public function scopeForBranch(Builder $query, ?int $branchId): Builder
    {
        return $query->when($branchId, fn (Builder $q) => $q->where('branch_id', $branchId));
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true);
    }

    /**
     * @return list<string>
     */
    public static function categories(): array
    {
        return ['Breakfast', 'Lunch', 'Dinner', 'Snacks', 'Drinks', 'Dessert'];
    }

    /**
     * Resolve selected menu rows ({menu_item_id, quantity}) into order items
     * with snapshots of name/price plus the computed order total.
     *
     * @param  array<int, array{menu_item_id: int|string, quantity: int|string}>  $rows
     * @return array{items: list<array<string, mixed>>, total_amount: float}
     */
    public static function composeOrder(array $rows, ?int $branchId = null): array
    {
        $branchId ??= ContextualBranch::id();
        $ids = collect($rows)->pluck('menu_item_id')->map(fn ($id) => (int) $id)->unique();

        $menuItems = static::query()
            ->forBranch($branchId)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $items = [];
        foreach ($rows as $row) {
            $menuItem = $menuItems->get((int) $row['menu_item_id']);
            if (! $menuItem) {
                continue;
            }

            $quantity = max(1, (int) $row['quantity']);
            $items[] = [
                'menu_item_id' => $menuItem->id,
                'name' => $menuItem->name,
                'category' => $menuItem->category,
                'price' => (float) $menuItem->price,
                'quantity' => $quantity,
                'subtotal' => round((float) $menuItem->price * $quantity, 2),
            ];
        }

        return [
            'items' => $items,
            'total_amount' => round(collect($items)->sum('subtotal'), 2),
        ];
    }
}

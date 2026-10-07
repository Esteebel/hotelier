<?php

namespace App\Observers;

use App\Models\HotelBranch;
use Illuminate\Support\Str;

class HotelBranchObserver
{
    public function creating(HotelBranch $branch): void
    {
        $branch->slug = $this->ensureUniqueSlug($branch);
    }

    public function updating(HotelBranch $branch): void
    {
        if ($branch->isDirty('name')) {
            $branch->slug = $this->ensureUniqueSlug($branch);
        }
    }

    private function ensureUniqueSlug(HotelBranch $branch): string
    {
        $base = Str::slug($branch->name) ?: 'branch';

        $slug = $base;
        $suffix = 2;

        while (
            HotelBranch::query()
                ->withoutGlobalScope('organization')
                ->where('slug', $slug)
                ->when($branch->exists, fn ($query) => $query->whereKeyNot($branch->getKey()))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
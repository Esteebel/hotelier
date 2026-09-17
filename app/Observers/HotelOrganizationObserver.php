<?php

namespace App\Observers;

use App\Models\HotelOrganization;
use Illuminate\Support\Str;

class HotelOrganizationObserver
{
    public function creating(HotelOrganization $hotel): void
    {
        $hotel->slug = $this->ensureUniqueSlug($hotel);
    }

    public function updating(HotelOrganization $hotel): void
    {
        if ($hotel->isDirty('name')) {
            $hotel->slug = $this->ensureUniqueSlug($hotel);
        }
    }

    private function ensureUniqueSlug(HotelOrganization $hotel): string
    {
        $base = Str::slug($hotel->name) ?: 'hotel';

        $slug = $base;
        $suffix = 2;

        while (
            HotelOrganization::query()
                ->where('slug', $slug)
                ->when($hotel->exists, fn ($query) => $query->whereKeyNot($hotel->getKey()))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
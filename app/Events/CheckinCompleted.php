<?php

namespace App\Events;

use App\Models\Checkin;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CheckinCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Checkin $checkin,
        public readonly ?int $branchId,
    ) {}
}

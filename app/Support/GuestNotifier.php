<?php

namespace App\Support;

use App\Models\Guest;
use App\Models\Notification;
use App\Models\User;

class GuestNotifier
{
    /**
     * Notify every user account linked to a guest profile.
     *
     * Silently skipped when the guest has no portal account.
     */
    public static function notify(
        ?Guest $guest,
        string $title,
        string $message,
        string $type = 'info',
    ): void {
        if (! $guest) {
            return;
        }

        $users = User::query()
            ->where('guest_id', $guest->id)
            ->get();

        foreach ($users as $user) {
            Notification::create([
                'notification_number' => Notification::nextNotificationNumber(),
                'branch_id' => $guest->branch_id,
                'user_id' => $user->id,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'status' => 'unread',
                'notifiable_type' => $guest->getMorphClass(),
                'notifiable_id' => $guest->id,
            ]);
        }
    }
}

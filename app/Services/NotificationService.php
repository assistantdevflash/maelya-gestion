<?php

namespace App\Services;

use App\Models\Notif;
use App\Models\User;

class NotificationService
{
    public static function notifyUser(User $user, string $type, string $titre, string $corps, string $url = '/'): void
    {
        Notif::create([
            'user_id' => $user->id,
            'type'    => $type,
            'titre'   => $titre,
            'corps'   => $corps,
            'url'     => $url,
        ]);
    }

    public static function notifyAdmins(string $type, string $titre, string $corps, string $url = '/admin'): void
    {
        User::where('role', 'super_admin')->each(function (User $admin) use ($type, $titre, $corps, $url) {
            static::notifyUser($admin, $type, $titre, $corps, $url);
        });
    }
}

<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordMaelya extends ResetPassword
{
    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe — ' . config('app.name'))
            ->view('emails.reset-password', ['url' => $url]);
    }
}

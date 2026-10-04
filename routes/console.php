<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:make-admin {email}', function (string $email) {
    $user = User::where('email', strtolower($email))->first();

    if (! $user) {
        $this->error("No user with email {$email}. Register the account first.");

        return 1;
    }

    $user->role = UserRole::Admin;
    $user->email_verified_at ??= now();
    $user->save();

    $this->info("{$user->email} is now a platform admin.");
})->purpose('Promote a registered user to platform admin');

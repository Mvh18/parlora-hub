<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * The only way to change a user's admin flag: no HTTP route can set it.
 */
#[Signature('hub:make-admin {email : Email of an existing account} {--revoke : Remove the admin flag instead of granting it}')]
#[Description('Grant or revoke admin access (Horizon, and later the content panel) for a user')]
class MakeAdminCommand extends Command
{
    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $user = User::where('email', $email)->first();

        if ($user === null) {
            $this->components->error("No account uses the email {$email}.");

            return self::FAILURE;
        }

        $grant = ! $this->option('revoke');
        $user->forceFill(['is_admin' => $grant])->save();

        $this->components->info($grant
            ? "{$email} is now an admin."
            : "{$email} is no longer an admin.");

        return self::SUCCESS;
    }
}

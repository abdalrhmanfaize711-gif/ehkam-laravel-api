<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;

class DeleteExpiredTokens extends Command
{
    protected $signature = 'sanctum:delete-expired';

    protected $description = 'Delete expired Sanctum tokens';

    public function handle(): int
    {
        $deleted = PersonalAccessToken::whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->delete();

        $this->info("Deleted {$deleted} expired tokens.");

        return self::SUCCESS;
    }
}
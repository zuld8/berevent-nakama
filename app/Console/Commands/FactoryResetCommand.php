<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class FactoryResetCommand extends Command
{
    protected $signature = 'app:factory-reset
        {--yes : Non-interactive mode, assume yes}
        {--wipe-payment-config=0 : Also wipe payment channels/methods}
        {--clear-categories=0 : Also clear categories}
        {--purge-files=0 : Purge uploaded files on s3 (articles/*)}';

    protected $description = 'Clear application data (keep organizations and users)';

    public function handle(): int
    {
        if (! $this->option('yes')) {
            if (! $this->confirm('This will DELETE most data except organizations and users. Continue?')) {
                $this->warn('Aborted.');
                return self::SUCCESS;
            }
        }

        $wipePayment = (bool) $this->option('wipe-payment-config');
        $clearCategories = (bool) $this->option('clear-categories');
        $purgeFiles = (bool) $this->option('purge-files');

        $tables = [
            'ledger_entries',
            'payout_items',
            'payouts',
            'payments',
            'webhook_events',
            'donations',
            'campaign_articles',
            'campaign_media',
            'campaign_category',
            'campaigns',
            'heroes',
            'wallets',
        ];

        if ($wipePayment) {
            $tables[] = 'payment_methods';
            $tables[] = 'payment_channels';
        }
        if ($clearCategories) {
            $tables[] = 'categories';
        }

        $driver = DB::getDriverName();
        $this->info('Disabling foreign key checks for driver: ' . $driver);
        $this->setForeignKeyChecks(false, $driver);

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                $this->line("- skip (no table): $table");
                continue;
            }
            try {
                DB::table($table)->truncate();
                $this->line("- truncated: $table");
            } catch (\Throwable $e) {
                // Fallback: delete if truncate not supported
                DB::table($table)->delete();
                $this->line("- deleted (fallback): $table");
            }
        }

        $this->setForeignKeyChecks(true, $driver);

        if ($purgeFiles) {
            try {
                Storage::disk('s3')->deleteDirectory('articles');
                $this->line('- purged s3://articles');
            } catch (\Throwable $e) {
                $this->warn('Failed to purge files: ' . $e->getMessage());
            }
        }

        $this->info('Factory reset complete.');
        return self::SUCCESS;
    }

    protected function setForeignKeyChecks(bool $enable, string $driver): void
    {
        try {
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=' . ($enable ? '1' : '0'));
            } elseif ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ' . ($enable ? 'ON' : 'OFF'));
            } elseif ($driver === 'pgsql') {
                // In Postgres, use transaction + defer constraints pattern; for simplicity we skip.
            }
        } catch (\Throwable $e) {
            $this->warn('Could not toggle foreign key checks: ' . $e->getMessage());
        }
    }
}


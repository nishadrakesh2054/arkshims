<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GoLiveCheck extends Command
{
    protected $signature = 'ims:go-live-check {--url=http://localhost:8000}';

    protected $description = 'Pre-production checklist: env, migrations, stock integrity, scheduler, and manual walkthrough';

    public function handle(): int
    {
        $this->components->info('IMS Go-Live Checklist');
        $this->newLine();

        $failures = 0;

        $failures += $this->checkEnvironment();
        $failures += $this->checkDatabase();
        $failures += $this->checkScheduler();
        $failures += $this->checkStockIntegrity();
        $failures += $this->printManualWalkthrough();

        $this->newLine();

        if ($failures > 0) {
            $this->components->error("Go-live check finished with {$failures} issue(s). Resolve before production.");

            return self::FAILURE;
        }

        $this->components->success('Go-live check passed. Ready for controlled production rollout.');

        return self::SUCCESS;
    }

    protected function checkEnvironment(): int
    {
        $this->components->info('Environment');

        $issues = 0;

        if (config('app.debug')) {
            $this->components->warn('  APP_DEBUG is true — set APP_DEBUG=false in production .env');
            $issues++;
        } else {
            $this->line('  <fg=green>✓</> APP_DEBUG=false');
        }

        if (blank(config('app.key'))) {
            $this->components->error('  APP_KEY is missing — run: php artisan key:generate');
            $issues++;
        } else {
            $this->line('  <fg=green>✓</> APP_KEY is set');
        }

        if (config('app.env') === 'local') {
            $this->components->warn('  APP_ENV=local — set APP_ENV=production on the live server');
            $issues++;
        } else {
            $this->line('  <fg=green>✓</> APP_ENV='.config('app.env'));
        }

        $this->newLine();

        return $issues;
    }

    protected function checkDatabase(): int
    {
        $this->components->info('Database');

        $issues = 0;

        try {
            DB::connection()->getPdo();
            $this->line('  <fg=green>✓</> Database connection OK');
        } catch (\Throwable $exception) {
            $this->components->error('  Database connection failed: '.$exception->getMessage());
            $issues++;

            return $issues;
        }

        if (Schema::hasTable('migrations')) {
            $status = Artisan::call('migrate:status', [], $this->output);

            if ($status !== self::SUCCESS) {
                $issues++;
            }
        }

        $this->newLine();

        return $issues;
    }

    protected function checkScheduler(): int
    {
        $this->components->info('Scheduler');

        $issues = 0;

        $this->line('  Add this cron entry on the production server:');
        $this->line('  <fg=cyan>* * * * * cd '.base_path().' && php artisan schedule:run >> /dev/null 2>&1</>');
        $this->newLine();
        $this->line('  Scheduled tasks:');
        $this->call('schedule:list');

        $this->newLine();

        return $issues;
    }

    protected function checkStockIntegrity(): int
    {
        $this->components->info('Stock integrity');

        return Artisan::call('ims:verify', [
            '--url' => $this->option('url'),
        ], $this->output) === self::SUCCESS ? 0 : 1;
    }

    protected function printManualWalkthrough(): int
    {
        $baseUrl = rtrim((string) $this->option('url'), '/');

        $this->newLine();
        $this->components->info('Manual walkthrough (complete once before go-live)');

        $steps = [
            'Login to admin panel',
            'Create a material receipt → confirm Raw Stock Ledger shows IN',
            'Create a repackaging batch → confirm raw OUT + FG IN',
            'Create a dispatch → confirm FG OUT',
            'Create a stock adjustment → confirm ADJUSTMENT in ledger',
            'Check Stock Analytics dashboard and Reports Center',
            'Reverse one adjustment (delete) → confirm stock restored',
        ];

        foreach ($steps as $index => $step) {
            $this->line('  '.($index + 1).'. '.$step);
        }

        $this->newLine();
        $this->line("  Admin URL: {$baseUrl}/admin");

        return 0;
    }
}

<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // Register custom commands here
    ];

    /**
     * Define the application's command schedule.
     *
     * Schedule tasks: backup, analytics, queue monitoring, security scans
     * Use onOneServer() for distributed environments to prevent duplicate runs
     */
    protected function schedule(Schedule $schedule): void
    {
        // Daily backup at 2 AM
        $schedule->command('backup:run')
            ->dailyAt('02:00')
            ->onOneServer()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/backup.log'))
            ->emailOutputOnFailure(config('mail.from.address'));

        // Hourly analytics processing
        $schedule->command('analytics:process')
            ->hourly()
            ->onOneServer()
            ->withoutOverlapping()
            ->runInBackground();

        // Queue monitoring every 15 minutes
        $schedule->command('queue:monitor')
            ->everyFifteenMinutes()
            ->onOneServer();

        // Weekly security scan on Sundays at 3 AM
        $schedule->command('security:scan')
            ->weeklyOn(0, '03:00')
            ->onOneServer()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/security-scan.log'));

        // Clear expired sessions daily
        $schedule->command('session:gc')
            ->daily()
            ->onOneServer();

        // Prune old failed jobs weekly
        $schedule->command('queue:prune-failed --hours=168')
            ->weekly()
            ->onOneServer();

        // Generate sitemap daily at 4 AM
        $schedule->command('sitemap:generate')
            ->dailyAt('04:00')
            ->onOneServer()
            ->runInBackground();

        // Cache optimization daily at 5 AM
        $schedule->command('cache:optimize')
            ->dailyAt('05:00')
            ->onOneServer();

        // Engagement scoring every 30 minutes
        $schedule->command('engagement:calculate')
            ->everyThirtyMinutes()
            ->onOneServer()
            ->runInBackground();

        // Send scheduled notifications every 5 minutes
        $schedule->command('notifications:send')
            ->everyFiveMinutes()
            ->onOneServer()
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

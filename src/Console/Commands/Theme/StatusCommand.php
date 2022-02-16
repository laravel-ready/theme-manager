<?php

namespace LaravelReady\ThemeManager\Console\Commands\Theme;

use Illuminate\Support\Str;
use Illuminate\Console\Command;

use LaravelReady\ThemeManager\Support\ThemeSupport;
use LaravelReady\ThemeManager\Services\ThemeManager;

class StatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'theme-manager:status {theme} {status}';

    /**
     * Command description
     *
     * @var string
     */
    protected $description = 'Update theme status';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->askTheme();
    }

    private function askTheme()
    {
        $groupThemePair = $this->argument('theme');
        $status = $this->argument('status');

        if ($status != 'true' && $status != 'false') {
            return $this->error('Please enter valid status value. Only "true" or "false" accepting.');
        }

        $status = $status === 'true';

        if (!$groupThemePair) {
            $groupThemePair = $this->ask('Theme vendor/theme');

            if (!$groupThemePair) {
                return $this->error('Please enter vendor/theme');
            }
        }

        if ($groupThemePair == 'all') {
            if (ThemeManager::setThemeStatusAll($status)) {
                $statusText = $status ? 'active' : 'passive';

                return $this->info("All themes statuses updated as \"{$statusText}\".");
            }

            return $this->info('Themes status could not updated.');
        }

        if (ThemeManager::getTheme($groupThemePair)) {
            if (ThemeManager::setThemeStatus($groupThemePair, $status)) {
                $statusText = $status ? 'active' : 'passive';

                return $this->info("Theme \"{$groupThemePair}\" status updated as \"{$statusText}\".");
            }

            return $this->info('Theme status could not updated.');
        }

        return $this->error('Requested theme not found.');
    }
}

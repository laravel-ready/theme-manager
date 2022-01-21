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
     * Update theme status
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
        $themeName = $this->argument('theme');
        $status = $this->argument('status');

        if ($status != 'true' && $status != 'false') {
            return $this->error('Please enter valid status value. Only "true" or "false" accepting.');
        }

        $status = $status === 'true';

        if (!$themeName) {
            $themeName = $this->ask('Theme group:theme');

            if (!$themeName) {
                return $this->error('Please enter group:theme');
            }
        }

        if ($themeName == 'all') {
            if (ThemeManager::setThemeStatusAll($status)) {
                $statusText = $status ? 'active' : 'passive';

                return $this->info("All themes statuses updated as \"{$statusText}\".");
            }

            return $this->info('Themes status could not updated.');
        }

        ThemeSupport::splitGroupTheme($themeName, $group, $theme);

        if ($group && $theme) {
            if (ThemeManager::getTheme($theme, $group)) {
                if (ThemeManager::setThemeStatus($theme, $group, $status)) {
                    $statusText = $status ? 'active' : 'passive';

                    return $this->info("Theme \"{$themeName}\" status updated as \"{$statusText}\".");
                }

                return $this->info('Theme status could not updated.');
            }

            return $this->error('Requested theme not found.');
        }

        return $this->error('Please enter valid theme');
    }
}

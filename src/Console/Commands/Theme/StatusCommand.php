<?php

namespace LaravelReady\ThemeManager\Console\Commands\Theme;

use Illuminate\Support\Str;
use Illuminate\Console\Command;

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
        $status = $this->argument('status') === 'true';

        if (!$themeName) {
            $themeName = $this->ask('Theme group:theme');

            if (!$themeName) {
                return $this->error('Please enter group:theme');
            }
        }

        if (Str::contains($themeName, ':')) {
            $groupTheme = explode(':', $themeName, 2);

            if (count($groupTheme) == 2) {
                $theme = ThemeManager::getTheme($groupTheme[1], $groupTheme[0]);

                if ($theme) {
                    if (ThemeManager::setThemeStatus($groupTheme[1], $groupTheme[0], $status)) {
                        return $this->info("Theme \"{$themeName}\" status updated.");
                    }

                    return $this->info('Theme status could not updated.');
                } else {
                    return $this->error('Requested theme not found.');
                }
            }
        } else {
            return $this->error('Please enter valid theme');
        }
    }
}

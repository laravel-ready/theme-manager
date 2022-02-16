<?php

namespace LaravelReady\ThemeManager\Console\Commands\Theme;

use Illuminate\Console\Command;

use LaravelReady\ThemeManager\Services\ThemeManager;

class PackCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'theme-manager:pack {theme}';

    /**
     * Command description
     *
     * @var string
     */
    protected $description = 'Pack selected theme';

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

        if (!$groupThemePair) {
            $groupThemePair = $this->ask('Theme vendor/theme');

            if (!$groupThemePair) {
                return $this->error('Please enter vendor/theme');
            }
        }

        $theme = ThemeManager::getTheme($groupThemePair);

        if ($theme) {
            $result = $theme->pack();

            if ($result) {
                return $this->info("Theme \"{$groupThemePair}\" packaged and saved own vendor folder.");
            }

            return $this->info('Theme could not packaged.');
        }

        return $this->error("Requested theme \"{$groupThemePair}\" not found.");
    }
}

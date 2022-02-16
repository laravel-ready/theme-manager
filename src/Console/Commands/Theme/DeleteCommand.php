<?php

namespace LaravelReady\ThemeManager\Console\Commands\Theme;

use Illuminate\Console\Command;

use LaravelReady\ThemeManager\Services\ThemeManager;

class DeleteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'theme-manager:destroy {theme}';

    /**
     * Command description
     *
     * @var string
     */
    protected $description = 'Delete selected theme';

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
            if ($this->askConfirmation()) {
                $result = $theme->delete();

                if ($result) {
                    return $this->info("Theme \"{$groupThemePair}\" deleted.");
                }
            }

            return $this->info('Theme could not deleted.');
        }

        return $this->error("Requested theme \"{$groupThemePair}\" not found.");
    }

    private function askConfirmation()
    {
        $confirmation = $this->ask('This theme will be delete. Are you sure? (yes/no)');

        if ($confirmation !== null) {
            if ($confirmation == 'yes') {
                return true;
            }

            $this->warn('Canceled theme delete operation.');

            return false;
        } else {
            return $this->askConfirmation();
        }
    }
}

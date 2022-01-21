<?php

namespace LaravelReady\ThemeManager\Console\Commands\Theme;

use Illuminate\Support\Str;
use Illuminate\Console\Command;

use LaravelReady\ThemeManager\Services\ThemeManager;

class CreateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'theme-manager:make';

    /**
     * Create new theme
     *
     * @var string
     */
    protected $description = 'Create new theme';

    /**
     * New theme variable
     */
    protected $theme = [
        'version' => '1.0.0',
        'status' => true
    ];

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
        $this->askThemeName();

        $this->askDescription();

        $this->askAuthor();

        $this->askGroup();

        $reesult = ThemeManager::createTheme($this->theme);

        if (!$reesult['result']) {
            return $this->error($reesult['message']);
        }

        $this->info($reesult['message']);
    }

    private function askThemeName()
    {
        $themeName = $this->ask('Theme Name');

        if (!$themeName) {
            $this->askThemeName();
        }

        $this->theme['name'] = $themeName;
        $this->theme['alias'] = Str::slug($themeName);
    }

    private function askDescription()
    {
        $description = $this->ask('Theme Description /optional/');

        $this->theme['description'] = $description;
    }

    private function askAuthor()
    {
        $authorName = $this->ask('Author Name');

        if (!$authorName) {
            $this->askAuthor();
        }

        $authorContact = $this->ask('Author Contact Address or Email /optional/');

        $this->theme['authors'][] = [
            'name' => $authorName,
            'contact' => $authorContact
        ];
    }

    private function askGroup()
    {
        $themeGroup = $this->ask('Theme Group');

        $this->theme['group'] = Str::slug($themeGroup);
    }
}

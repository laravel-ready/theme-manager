<?php

namespace LaravelReady\ThemeManager\Console\Commands\Theme;

use Illuminate\Console\Command;
use LaravelReady\ThemeManager\Services\Theme;

class CreateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'theme-manager:make';

    /**
     * Command description
     *
     * @var string
     */
    protected $description = 'Create new theme';

    /**
     * New theme variable
     */
    protected $theme;

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
        $this->theme = new Theme();

        $this->askGroup();

        $this->askVendor();

        $this->askThemeName();

        $this->askDescription();

        $this->askAuthor();

        $result = $this->theme->create();

        if (!$result['result']) {
            return $this->error($result['message']);
        }

        $this->info($result['message']);
    }

    /**
     * Ask for theme group name
     */
    private function askGroup()
    {
        $themeGroup = $this->ask('Theme Group (as slug. web, admin etc)');

        $this->theme->setGroup($themeGroup);
    }

    /**
     * Ask for theme vendor slug
     */
    private function askVendor()
    {
        $vendorSlug = $this->ask('Theme Vendor (as slug)');

        if (!$vendorSlug) {
            $this->askVendor();
        }

        $this->theme->setVendor($vendorSlug);
    }

    /**
     * Ask for theme name
     */
    private function askThemeName()
    {
        $themeName = $this->ask('Theme Name');

        if (!$themeName) {
            $this->askThemeName();
        }

        $this->theme->setName($themeName);
        $this->theme->setTheme($themeName);
    }

    /**
     * Ask for theme description
     */
    private function askDescription()
    {
        $description = $this->ask('Theme Description (optional)');

        if (!empty($description)) {
            $this->theme->setDescription($description);
        }
    }

    /**
     * Ask for theme author name or email
     */
    private function askAuthor()
    {
        $authorName = $this->ask('Author Name');

        if (!$authorName) {
            $this->askAuthor();
        }

        $authorContact = $this->ask('Author Contact Address or Email (optional)');

        $this->theme->addAuthor($authorName, $authorContact);
    }
}

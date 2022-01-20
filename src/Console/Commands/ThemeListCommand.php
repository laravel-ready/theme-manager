<?php

namespace LaravelReady\ThemeManager\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use LaravelReady\ThemeManager\Services\ThemeManager;

class ThemeListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'theme-manager:list';

    /**
     * List all installed themes
     *
     * @var string
     */
    protected $description = 'List all installed themes';

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
        $themes = ThemeManager::scanThemes(true);

        if ($themes) {
            $headers = [
                'Index',
                'Name',
                'Alias',
                'Group',
                'Description',
                'Authors',
                'Version'
            ];

            $themeList = [];
            $index = 0;

            $defaultTheme = Config::get('theme-manager.default_theme');

            foreach ($themes as $group => $themes) {
                foreach ($themes as $theme) {
                    ++$index;

                    $authors = array_map(function ($author) {
                        return $author->name;
                    }, $theme->authors);

                    $authors = implode(', ', $authors);

                    $isDefaultTheme = false;

                    if ($defaultTheme) {
                        if (is_string($defaultTheme)) {
                            $isDefaultTheme = $defaultTheme == "{$group}:{$theme->alias}";
                        } else if (is_array($defaultTheme)) {
                            $isDefaultTheme = in_array("{$group}:{$theme->alias}", $defaultTheme);
                        }
                    }

                    $themeList[] = [
                        'Index' => " [{$index}]",
                        'Name' => $theme->name . ($isDefaultTheme ? ' (Default)' : ''),
                        'Alias' => $theme->alias,
                        'Group' => $group,
                        'Description' => Str::limit($theme->description, 30, '...'),
                        'Authors' => $authors,
                        'Version' => $theme->version
                    ];
                }
            }

            $this->info('');
            $this->info('⚠️  Check the "theme-config.json" file in the own theme folder for more information.');
            $this->info('');

            return $this->table($headers, $themeList);
        }

        return $this->error('There is no theme installed.');
    }
}

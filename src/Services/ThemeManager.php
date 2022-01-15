<?php

namespace LaravelReady\ThemeManager\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

use LaravelReady\ThemeManager\Exceptions\Theme\ThemeManagerException;

class ThemeManager
{
    /**
     * Get asset public url
     *
     * @param string $asset asset relative path
     * @param object $theme current theme
     *
     * @return string
     */
    public function assetUrl(string $asset, object $theme): string
    {
        $asset = trim($asset, '/');

        return app('url')->asset("/themes/{$theme->group}/{$theme->alias}/{$asset}");
    }

    /**
     * Get asset local path
     *
     * @param string $asset
     * @param object $theme
     *
     * @return string
     */
    public function assetPath(string $asset, object $theme): string
    {
        $asset = trim($asset, '/');

        return base_path() . "/public/themes/{$theme->group}/{$theme->alias}/{$asset}";
    }

    /**
     * Set current theme
     *
     * @param string $themeAlias
     * @param string $group
     */
    public function setTheme(string $themeAlias, string $group = 'web'): void
    {
        Config::set('theme-manager.active_theme_alias', $themeAlias);
        Config::set('theme-manager.current_theme', $this->getTheme($themeAlias, $group));
    }

    /**
     * Get target theme
     *
     * @param string $themeAlias
     * @param string $group
     *
     * @throws ThemeManagerException
     *
     * @return mixed
     */
    public function getTheme(string $themeAlias, string $group = 'web'): mixed
    {
        $cacheKey = 'theme-manager.themes';
        $themes = Cache::get($cacheKey);
        $group = trim($group);

        if ($themes && is_array($themes)) {
            if (isset($themes[$group])) {
                return collect($themes[$group])->first(function ($value) use ($themeAlias) {
                    return $value->alias == $themeAlias;
                });
            }

            throw new ThemeManagerException("Requested theme group: '{$group}' not found.");
        }

        return null;
    }

    /**
     * Scan installed themes
     *
     * @param bool $reScan
     *
     * @return array
     */
    public function scanThemes(bool $reScan = false): array
    {
        $cacheKey = 'theme-manager.themes';

        if ($reScan) {
            Cache::forget($cacheKey);
        }

        return Cache::rememberForever($cacheKey, function () use ($cacheKey) {
            $themesRootFolder = Config::get('theme-manager.themes_root_folder');
            $themesBasePath = base_path() . "/{$themesRootFolder}";

            if (!File::exists($themesBasePath)) {
                File::makeDirectory($themesBasePath);
            }

            $themeGroups = new \DirectoryIterator($themesBasePath);

            $themeList = [];

            // in theme groups
            foreach ($themeGroups as $themeGroup) {
                if ($themeGroup->isDir() && !$themeGroup->isDot()) {
                    $themes = new \DirectoryIterator($themeGroup->getRealPath());

                    // in group's themes
                    foreach ($themes as $theme) {
                        if ($theme->isDir() && !$theme->isDot()) {
                            $themePath = $theme->getRealPath();
                            $themeConfigFile = "{$themePath}/theme-configs.json";

                            if (File::exists($themeConfigFile)) {
                                $themeConfigs = json_decode(File::get($themeConfigFile));
                                $themeConfigs->path = $themePath;
                                $themeConfigs->views = "{$themePath}/views";

                                if (File::exists("{$themePath}/preview.png")){
                                    $themeConfigs->preview = "{$themePath}/preview.png";
                                } else if (File::exists("{$themePath}/preview.jpg")){
                                    $themeConfigs->preview = "{$themePath}/preview.jpg";
                                }

                                if ($themeConfigs->preview) {
                                    $themeConfigs->preview = base64_encode(File::get($themeConfigs->preview));
                                }

                                $themeList[$themeGroup->getFilename()][] = $themeConfigs;
                            }
                        }
                    }
                }
            }

            if (!count($themeList)) {
                Cache::forget($cacheKey);
            }

            return $themeList;
        });

        return null;
    }
}
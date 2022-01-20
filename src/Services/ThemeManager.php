<?php

namespace LaravelReady\ThemeManager\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

use LaravelReady\ThemeManager\Exceptions\Theme\ThemeManagerException;

class ThemeManager
{
    public function __construct()
    {
        $rootFolder = Config::get('theme-manager.themes_root_folder');

        if (!File::exists($rootFolder)) {
            File::makeDirectory($rootFolder);
        }
    }

    /**
     * Get asset public static url
     *
     * @param string $asset asset relative path
     * @param object $theme current theme
     *
     * @return string
     */
    public static function assetUrl(string $asset, object $theme): string
    {
        $asset = trim($asset, '/');

        $themesRootFolder = Config::get('theme-manager.themes_root_folder');

        return app('url')->asset("/{$themesRootFolder}/{$theme->group}/{$theme->alias}/{$asset}");
    }

    /**
     * Get asset local path
     *
     * @param string $asset
     * @param object $theme
     *
     * @return string
     */
    public static function assetPath(string $asset, object $theme): string
    {
        $asset = trim($asset, '/');

        $themesRootFolder = Config::get('theme-manager.themes_root_folder');

        return base_path() . "/public/{$themesRootFolder}/{$theme->group}/{$theme->alias}/{$asset}";
    }

    /**
     * Set current theme
     *
     * @param string $themeAlias
     * @param string $group
     */
    public static function setTheme(string $themeAlias, string $group = 'web'): mixed
    {
        $theme = self::getTheme($themeAlias, $group);

        Config::set('theme-manager.current_theme', $theme);

        return $theme;
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
    public static function getTheme(string $themeAlias, string $group = 'web'): mixed
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

            throw new ThemeManagerException("Requested theme group: \"{$group}\" not found.");
        }

        return null;
    }

    /**
     * Get current theme
     *
     * @return mixed
     */
    public static function getCurrentTheme(): mixed
    {
        return Config::get('theme-manager.current_theme');
    }

    /**
     * Scan installed themes
     *
     * @param bool $reScan
     *
     * @return array
     */
    public static function scanThemes(bool $reScan = false): array
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

                                $themeConfigs->preview = null;

                                if (File::exists("{$themePath}/preview.png")) {
                                    $themeConfigs->preview = "{$themePath}/preview.png";
                                } else if (File::exists("{$themePath}/preview.jpg")) {
                                    $themeConfigs->preview = "{$themePath}/preview.jpg";
                                }

                                if ($themeConfigs->preview) {
                                    $themeConfigs->preview = base64_encode(File::get($themeConfigs->preview));
                                } else {
                                    $themeConfigs->preview_default = base64_encode(File::get(__DIR__ . '/../../resources/images/preview-default.jpg'));
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

    /**
     * Rescan themes
     *
     * @return array
     */
    public static function reScanThemes(): array
    {
        return self::scanThemes(true);
    }

    /**
     * Set default theme and group
     *
     * @param string|array $theme
     * @param string|array $group
     */
    public static function addDefaultTheme(string|array $groupThemePair): void
    {
        $cacheKey = 'theme-manager.preconfigs';

        $preConfigCache = Cache::get($cacheKey) ?? [];

        $preConfigCache['default_theme'] = $groupThemePair;

        Cache::put($cacheKey, $preConfigCache);
    }

    /**
     * Get default theme
     *
     * @return null|string|array
     */
    public static function getDefaultTheme(): mixed
    {
        return Config::get('theme-manager.default_theme');
    }
}

<?php

namespace LaravelReady\ThemeManager\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

use LaravelReady\ThemeManager\Support\ThemeSupport;
use LaravelReady\ThemeManager\Traits\CacheKeyTrait;
use LaravelReady\ThemeManager\Services\Theme;
use LaravelReady\ThemeManager\Exceptions\Theme\ThemeManagerException;

class ThemeManager
{
    use CacheKeyTrait;

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

        return app('url')->asset("/{$themesRootFolder}/{$theme->vendor}/{$theme->theme}/{$asset}");
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

        return base_path() . "/public/{$themesRootFolder}/{$theme->vendor}/{$theme->theme}/{$asset}";
    }

    /**
     * Set current theme
     *
     * @param string $themeAliases
     */
    public static function setTheme(string $themeAliases): null|Theme
    {
        $theme = self::getTheme($themeAliases);

        if ($theme) {
            Config::set('theme-manager.current_theme', $theme);
        }

        return $theme;
    }

    /**
     * Get target Theme:class item
     *
     * @param string $themeAliases in vendor/theme format
     *
     * @throws ThemeManagerException
     *
     * @return mixed
     */
    public static function getTheme(string $themeAliases): null|Theme
    {
        $vendorTheme = ThemeSupport::parseThemeAliases($themeAliases, $vendor, $theme);

        if ($vendorTheme) {
            $themes = Cache::get(self::$themesCacheKey);

            if ($themes) {
                $themeItem = $themes->first(function (Theme $themeItem) use ($vendor, $theme) {
                    return $themeItem->vendor == $vendor && $themeItem->theme == $theme;
                });

                if ($themeItem) {
                    return $themeItem;
                }
            } else {
                throw new ThemeManagerException('There is no installed theme');
            }
        }

        return null;
    }

    /**
     * Get current theme
     *
     * @return mixed
     */
    public static function getCurrentTheme(): null|Theme
    {
        return Config::get('theme-manager.current_theme');
    }

    /**
     * Scan installed themes
     *
     * @param bool $reScan or use reScanThemes()
     *
     * @return Collection
     */
    public static function scanThemes(bool $reScan = false): Collection
    {
        if ($reScan) {
            Cache::forget(self::$themesCacheKey);
        }

        return Cache::rememberForever(self::$themesCacheKey, function () {
            $themesRootFolder = Config::get('theme-manager.themes_root_folder');
            $themesBasePath = base_path() . "/{$themesRootFolder}";

            if (!File::exists($themesBasePath)) {
                File::makeDirectory($themesBasePath);

                return null;
            }

            $vendors = new \DirectoryIterator($themesBasePath);
            $themeList = [];

            foreach ($vendors as $vendor) {
                if ($vendor->isDir() && !$vendor->isDot()) {
                    $themes = new \DirectoryIterator($vendor->getRealPath());

                    foreach ($themes as $theme) {
                        if ($theme->isDir() && !$theme->isDot()) {
                            $themeList[] = ThemeSupport::getThemeConfigs($theme->getRealPath(), $vendor->getFilename());
                        }
                    }
                }
            }

            if (count($themeList)) {
                return collect($themeList);
            }

            Cache::forget(self::$themesCacheKey);

            return null;
        });

        return null;
    }

    /**
     * Rescan themes
     *
     * @return Collection
     */
    public static function reScanThemes(): Collection
    {
        return self::scanThemes(true);
    }

    /**
     * Add default theme
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

    /**
     * Remove default theme
     *
     * @param string|array $theme
     * @param string|array $group
     */
    public static function removeDefaultTheme(string|array $groupThemePair): void
    {
        $cacheKey = 'theme-manager.preconfigs';

        $preConfigCache = Cache::get($cacheKey) ?? [];
        $defaultTheme = $preConfigCache['default_theme'] ?? [];

        if (is_string($defaultTheme) && is_string($groupThemePair)) {
            if ($defaultTheme == $groupThemePair) {
                $defaultTheme = [];
            }
        } else {
            $groupThemePair = is_string($groupThemePair) ? [$groupThemePair] : $groupThemePair;

            if (count($defaultTheme) > 0) {
                $defaultTheme = array_diff($defaultTheme, $groupThemePair);
            }
        }

        $preConfigCache['default_theme'] = $defaultTheme;

        Cache::put($cacheKey, $preConfigCache);
    }

    /**
     * Set theme status
     *
     * After theme status updated rescans themes
     *
     * @param string $themeAliases
     * @param bool $status
     *
     * @param bool
     */
    public static function setThemeStatus(string $themeAliases, bool $status): bool
    {
        $themes = self::scanThemes();

        if ($themes) {
            $result = self::getTheme($themeAliases)?->updateStatus($status);

            if ($result) {
                self::reScanThemes();
                
                return true;
            }
        }

        return false;
    }

    /**
     * Update all themes status
     *
     * @param bool $status
     *
     * @return bool
     */
    public static function setThemeStatusAll(bool $status): bool
    {
        $themes = self::scanThemes();

        foreach ($themes as $theme) {
            $theme->updateStatus($status);
        }

        ThemeManager::reScanThemes();

        return true;
    }
}

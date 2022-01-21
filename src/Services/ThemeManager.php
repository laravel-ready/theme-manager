<?php

namespace LaravelReady\ThemeManager\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

use LaravelReady\ThemeManager\Support\ThemeSupport;
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
     * Create new theme
     *
     * @param array $themeConfigs
     *
     * @return array
     */
    public static function createTheme(array $themeConfigs): array
    {
        $themesFolder = base_path(Config::get('theme-manager.themes_root_folder'));

        $themeFolder = "{$themesFolder}/{$themeConfigs['group']}/{$themeConfigs['alias']}";
        $themeTemplateFolder = __DIR__ . '/./../../resources/theme-template';

        if (!File::exists($themeFolder)) {
            File::makeDirectory($themeFolder, 0755, true);
        } else {
            return [
                'result' => false,
                'message' => "Theme folder already exists: {$themeConfigs['group']}:{$themeConfigs['alias']}"
            ];
        }

        if (File::exists($themeFolder)) {
            $isCopied = File::copyDirectory($themeTemplateFolder, $themeFolder);

            if ($isCopied) {
                $themeConfigFile = ThemeSupport::prettyJson($themeConfigs);

                $result = File::put("{$themeFolder}/theme-configs.json", $themeConfigFile);

                if (!$result) {
                    return [
                        'result' => false,
                        'message' => 'Theme configs could not created.'
                    ];
                }
            }

            self::reScanThemes();

            return [
                'result' => true,
                'message' => "Theme \"{$themeConfigs['group']}:{$themeConfigs['alias']}\" created successfully"
            ];
        }

        return [
            'result' => false,
            'message' => 'Theme folder not found. Please try again.'
        ];
    }

    /**
     * Delete selected theme
     *
     * @param string $theme
     * @param string $group
     *
     * @param bool
     */
    public static function deleteTheme(string $theme, string $group)
    {
        $themesFolder = base_path(Config::get('theme-manager.themes_root_folder'));

        $themeFolder = "{$themesFolder}/{$group}/{$theme}";

        if (File::exists($themeFolder)) {
            return File::deleteDirectory($themeFolder);
        }

        return false;
    }

    /**
     * Set theme status
     *
     * After theme status updated rescans themes
     *
     * @param string $theme
     * @param string $group
     * @param bool $status
     *
     * @param bool
     */
    public static function setThemeStatus(string $theme, string $group, bool $status): bool
    {
        $themesFolder = base_path(Config::get('theme-manager.themes_root_folder'));

        $themeConfigsFile = "{$themesFolder}/{$group}/{$theme}/theme-configs.json";

        if (File::exists($themeConfigsFile)) {
            $themeConfigs = json_decode(File::get($themeConfigsFile));

            if ($themeConfigs) {
                $themeConfigs->status = $status;

                $result = File::put($themeConfigsFile, ThemeSupport::prettyJson($themeConfigs));

                if ($result) {
                    self::reScanThemes();

                    return $result;
                }
            }
        }

        return false;
    }

    /**
     * Set status to all themes
     *
     * @param bool $thme
     */
    public static function setThemeStatusAll(bool $status): bool
    {
        $themeGroups = self::reScanThemes();
        $totalResult = true;

        if (count($themeGroups)) {
            foreach ($themeGroups as $group => $themeGroup) {
                foreach ($themeGroup as $theme) {
                    $result = self::setThemeStatus($theme->alias, $group, $status);

                    if (!$result) {
                        $totalResult = $result;
                    }
                }
            }
        }

        self::reScanThemes();

        return $totalResult;
    }
}

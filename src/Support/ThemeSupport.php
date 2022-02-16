<?php

namespace LaravelReady\ThemeManager\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

use LaravelReady\ThemeManager\Services\Theme;
use LaravelReady\ThemeManager\Traits\CacheKeyTrait;
use LaravelReady\ThemeManager\Exceptions\Theme\ThemeManagerException;

class ThemeSupport
{
    use CacheKeyTrait;

    /**
     * Extract blade directive arguments as array
     *
     * @param string $args
     *
     * @return array
     */
    public static function getDirectiveArguments(string $args): array
    {
        return explode(',', str_replace(['(', ')', ' ', "'"], '', $args));
    }

    /**
     * Return absolute bool value
     *
     * Input is not isset = true
     * Input isset and 'true' = true
     * Input isset and not 'false' = true
     *
     * @param mixed $args
     * @param int $index
     *
     * @return bool
     */
    public static function absoluteTrue(mixed $args, int $index): bool
    {
        return !isset($args[$index]) ||
            (isset($args[$index]) && $args[$index] === 'true') ||
            (isset($args[$index]) && $args[$index] !== 'false');
    }

    /**
     * Hand mixed arguments
     *
     * @param string $arg1
     * @param string $arg2
     * @param string $arg3
     */
    public static function handleArguments(string $arg1 = null, string $arg2 = null, string $arg3 = null)
    {
        $args = [];

        for ($i = 1; $i < 4; $i++) {
            $varName = "arg{$i}";

            if ($$varName) {
                $parameters = explode('=', $$varName);

                if (count($parameters) == 2) {
                    $args[trim($parameters[0])] = trim($parameters[1]);
                }
            }
        }

        return $args;
    }

    /**
     * Pretty preint JSON content
     *
     * @param mixed $content
     *
     * @return string
     */
    public static function prettyJson(mixed $content): string
    {
        return json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Get all themes in one list
     *
     * @return array|null
     */
    public static function simpleThemeList()
    {
        $themes = Cache::get(self::$themesCacheKey);

        if ($themes) {
            return $themes->map(function (Theme $theme) {
                return "{$theme->vendor}/{$theme->theme}";
            })->toArray();
        }

        return null;
    }

    /**
     * Parse theme aliases
     *
     * @param string $theme
     *
     * @return array|false
     */
    public static function parseThemeAliases(string $aliases, string &$vendor = null, string &$theme = null): array|false
    {
        $vendorThemePair = explode('/', $aliases);

        if (isset($vendorThemePair[1])) {
            $vendor = $vendorThemePair[0];
            $theme = $vendorThemePair[1];

            return [
                'vendor' => $vendor,
                'theme' => $theme
            ];
        }

        $themeList = self::simpleThemeList();

        $installedThemes = $themeList ? "Installed themes: " . implode(', ', $themeList) : '';

        throw new ThemeManagerException("Requested theme aliases are not valid: \"{$theme}\".
            Theme aliases must be in \"vendor/theme\" format. {$installedThemes}");
    }

    /**
     * Get theme configs
     *
     * @param string $themePath
     * @param string $vendor
     * @param string $themesRootFolder
     *
     * @return Theme
     */
    public static function getThemeConfigs(string $themePath, string $vendor = null)
    {
        $themeConfigFile = "{$themePath}/theme-configs.json";

        if (File::exists($themeConfigFile)) {
            $themeConfigs = json_decode(File::get($themeConfigFile), true);
            $themesRootFolder = Config::get('theme-manager.themes_root_folder');

            $themeConfigs['alias'] = "{$themeConfigs['vendor']}/{$themeConfigs['theme']}";
            $themeConfigs['path'] = $themePath;
            $themeConfigs['vendor'] = $themeConfigs['vendor'] ?? $vendor;
            $themeConfigs['views'] = "{$themePath}/views";
            $themeConfigs['preview'] = null;

            $themeAssetPath = "{$themePath}/public";
            $publicAssetPath = public_path("{$themesRootFolder}\\{$themeConfigs['vendor']}\\{$themeConfigs['theme']}");

            $themeConfigs['asset_path'] = $publicAssetPath;

            $themePreviewFileName = "{$themePath}/theme-preview";

            if (File::exists("{$themePreviewFileName}.png")) {
                $themeConfigs['preview'] = "{$themePreviewFileName}.png";
            } else if (File::exists("{$themePreviewFileName}.jpg")) {
                $themeConfigs['preview'] = "{$themePreviewFileName}.jpg";
            }

            if ($themeConfigs['preview']) {
                $themeConfigs['preview'] = base64_encode(File::get($themeConfigs['preview']));
            } else {
                $themeConfigs['preview_default'] = base64_encode(File::get(__DIR__ . '/../../resources/images/preview-default.jpg'));
            }

            if (
                !File::exists($publicAssetPath) &&
                (!empty(File::files($themeAssetPath)) && empty(File::files($publicAssetPath)))
            ) {
                File::copyDirectory($themeAssetPath, $publicAssetPath);
            }

            return new Theme($themeConfigs);
        }

        return null;
    }
}

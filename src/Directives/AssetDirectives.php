<?php

namespace LaravelReady\ThemeManager\Directives;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

use Illuminate\Support\Facades\Config;
use LaravelReady\ThemeManager\Support\ThemeSupport;

class AssetDirectives
{
    public $directives;

    public function __construct()
    {
        $this->directives = [
            'asset' => function ($args) {
                $args = ThemeSupport::getDirectiveArguments($args);

                return $this->asset($args[0], ThemeSupport::absoluteTrue($args, 1));
            },

            'assetLoad' => function ($args) {
                $args = ThemeSupport::getDirectiveArguments($args);

                return $this->assetLoad($args[0], $args[0] ?? null);
            },

            'svg' => function ($args) {
                $args = ThemeSupport::getDirectiveArguments($args);

                return $this->svg($args[0], $args[1] ?? null, $args[2] ?? null);
            }
        ];
    }

    /**
     * Get theme specific asset URL
     *
     * @param string $asset: $arg[0]
     * @param string $printVersion: $arg[1]
     *
     * @return string
     */
    public function asset(string $asset, string $printVersion): string
    {
        $theme = Config::get('theme-manager.current_theme');

        $assetPath = app('theme-manager')->assetUrl($asset, $theme);
        $version = $theme ? $theme->version : '1.0.x';

        return $printVersion ? ("{$assetPath}?v={$version}") : $assetPath;
    }

    /**
     * Get theme specific asset content
     *
     * @param string $asset: $arg[0]
     * @param string $defaultAsset: $arg[1]
     *
     * @return string
     */
    public function assetLoad(string $asset, string $defaultAsset = null): string
    {
        $theme = Config::get('theme-manager.current_theme');

        $assetPath = app('theme-manager')->assetPath($asset, $theme);

        if (File::exists($assetPath)) {
            return File::get($assetPath);
        } else if ($defaultAsset) {
            $assetPath = app('theme-manager')->assetPath($defaultAsset, $theme);

            if (File::exists($defaultAsset)) {
                return File::get($defaultAsset);
            }
        }

        return 'asset not found';
    }

    /**
     * Get SVG content as string
     *
     * @param string $svgName: $arg[0]
     * @param string $class: $arg[1]
     * @param string $style: $arg[2]
     *
     * @return string
     */
    public function svg(string $svgName, string $class = null, string $style = null): string
    {
        $theme = Config::get('theme-manager.current_theme');
        $cacheKey = "svg.{$svgName}";

        $svgCache = Cache::get($cacheKey);

        if ($svgCache == null) {
            $svgPath = public_path("themes\\{$theme->group}\\{$theme->alias}\\svg\\{$svgName}.svg");

            if (file_exists($svgPath)) {
                $svg = new \DOMDocument();
                $svg->load($svgPath);
                $svg->documentElement->setAttribute('class', "tm-svg {$class}");

                if ($style) {
                    $svg->documentElement->setAttribute('style', $style);
                }

                $svgCache = $svg->saveXML($svg->documentElement);

                Cache::put($cacheKey, $svgCache);

                return $svgCache;
            }

            return 'inconnotfound';
        }

        return $svgCache;
    }
}

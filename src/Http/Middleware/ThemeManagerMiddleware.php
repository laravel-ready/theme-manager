<?php

namespace LaravelReady\ThemeManager\Http\Middleware;

use Closure;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

use LaravelReady\ThemeManager\Support\ThemeSupport;
use LaravelReady\ThemeManager\Traits\CacheKeyTrait;
use LaravelReady\ThemeManager\Services\Theme;
use LaravelReady\ThemeManager\Services\ThemeManager;
use LaravelReady\ThemeManager\Exceptions\Theme\ThemeManagerException;


class ThemeManagerMiddleware
{
    use CacheKeyTrait;

    public function handle(Request $request, Closure $next, string $arg1 = null, string $arg2 = null)
    {
        if (!Cache::has(self::$themesCacheKey) || !Cache::get(self::$themesCacheKey)) {
            ThemeManager::reScanThemes();
        }

        $currentTheme = Config::get('theme-manager.current_theme');

        // check theme forced by service
        if ($currentTheme) {
            $this->checkThemeStatus($currentTheme);

            View::addNamespace('theme', $currentTheme->views);

            return $next($request);
        } else {
            $args = ThemeSupport::handleArguments($arg1, $arg2);

            $themeAliases = $args['theme'] ?? null;
            $vendor = $args['vendor'] ?? null;

            // check middleware theme
            if ($themeAliases) {
                ThemeSupport::parseThemeAliases($themeAliases, $vendor, $theme);

                if ($vendor && $theme) {
                    $currentTheme = ThemeManager::setTheme($themeAliases);

                    if ($currentTheme && $this->checkMiddlewareTheme($currentTheme)) {
                        return $next($request);
                    }
                }
            }

            $defaultTheme = Config::get('theme-manager.default_theme');

            // check default theme
            if ($defaultTheme && $vendor) {
                if ($this->checkDefaultTheme($defaultTheme, $vendor)) {
                    return $next($request);
                }
            }
        }

        throw new ThemeManagerException("Requested theme could not found.");
    }

    /**
     * Check and load middleware theme
     *
     * @param object $currentTheme
     * @param mixed $defaultTheme
     *
     * @return bool
     */
    private function checkMiddlewareTheme(Theme $currentTheme)
    {
        if ($currentTheme) {
            $this->checkThemeStatus($currentTheme);

            View::addNamespace('theme', $currentTheme->views);

            return true;
        }

        return false;
    }

    /**
     * Check and load default theme
     *
     * @param string|array $defaultThemeAliases
     * @param string $vendor middleware specific theme vendor
     *
     * @return bool
     */
    private function checkDefaultTheme(string|array $defaultThemeAliases, $vendor)
    {
        if (is_string($defaultThemeAliases)) {
            $defaultThemeAliases = [$defaultThemeAliases];
        }

        $themeAliases = null;

        foreach ($defaultThemeAliases as $themeAliases) {
            if (Str::startsWith($themeAliases, "{$vendor}:")) {
                $themeAliases = $themeAliases;

                break;
            }
        }

        if ($themeAliases) {
            $defaultTheme = ThemeManager::getTheme($themeAliases);

            if ($defaultTheme) {
                $this->checkThemeStatus($defaultTheme);

                $currentTheme = ThemeManager::setTheme($themeAliases);

                View::addNamespace('theme', $currentTheme->views);

                return true;
            }

            $themeAliases = implode(', ', $defaultThemeAliases);

            throw new ThemeManagerException("Configured default theme \"{$themeAliases}\" could not found.");
        }

        return false;
    }

    /**
     * Check theme is active
     *
     * @param object $theme
     */
    private function checkThemeStatus(Theme $theme)
    {
        if (!$theme->status) {
            throw new ThemeManagerException("Theme disabled.");
        }
    }
}

<?php

namespace LaravelReady\ThemeManager\Http\Middleware;

use Closure;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

use LaravelReady\ThemeManager\Support\ThemeSupport;
use LaravelReady\ThemeManager\Services\ThemeManager;
use LaravelReady\ThemeManager\Exceptions\Theme\ThemeManagerException;

class ThemeManagerMiddleware
{
    public function handle(Request $request, Closure $next, string $arg1 = null, string $arg2 = null, string $arg3 = null)
    {
        $cacheKey = 'theme-manager.themes';

        if (!Cache::has($cacheKey) || !Cache::get($cacheKey)) {
            ThemeManager::scanThemes(true);
        }

        $currentTheme = Config::get('theme-manager.current_theme');

        // check theme forced by service
        if ($currentTheme) {
            $this->checkThemeStatus($currentTheme);

            View::addNamespace('theme', $currentTheme->views);

            return $next($request);
        } else {
            $args = ThemeSupport::handleArguments($arg1, $arg2, $arg3);
            $theme = $args['theme'] ?? null;
            $group = $args['group'] ?? null;
            $restrictGroup = $args['restrict_group'] ?? null;

            $defaultTheme = Config::get('theme-manager.default_theme');

            // check middleware theme
            if ($theme && $group) {
                $currentTheme = ThemeManager::setTheme($theme, $group);

                if ($this->checkMiddlewareTheme($currentTheme, $restrictGroup, $defaultTheme)) {
                    return $next($request);
                }
            }

            // check default theme
            if ($defaultTheme && $group) {
                if ($this->checkDefaultTheme($defaultTheme, $group)) {
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
     * @param string $restrictGroup
     * @param mixed $defaultTheme
     *
     * @return bool
     */
    private function checkMiddlewareTheme(object $currentTheme, string $restrictGroup, mixed $defaultTheme)
    {
        if ($currentTheme) {
            $this->checkThemeStatus($currentTheme);

            if ($restrictGroup && $currentTheme->group !== $restrictGroup) {
                throw new ThemeManagerException("This route resticted only theme group:
                    \"{$restrictGroup}\", provided group: \"{$currentTheme->group}\"");
            }

            View::addNamespace('theme', $currentTheme->views);

            return true;
        }

        if ($defaultTheme == null) {
            throw new ThemeManagerException("Configured middleware theme \"{$currentTheme->group}:{$currentTheme->alias}\" could not found.");
        }

        return false;
    }

    /**
     * Check and load default theme
     *
     * @param string|array $defaultTheme
     * @param string $group
     *
     * @return bool
     */
    private function checkDefaultTheme(string|array $defaultTheme, string $group)
    {
        $theme = null;

        if (is_string($defaultTheme)) {
            ThemeSupport::splitGroupTheme($defaultTheme, $group, $theme);
        } else if ($group && is_array($defaultTheme)) {
            foreach ($defaultTheme as $themePair) {
                if (Str::startsWith($themePair, "{$group}:")) {
                    ThemeSupport::splitGroupTheme($themePair, $group, $theme);

                    break;
                }
            }
        }

        if ($theme && $group) {
            $currentTheme = ThemeManager::setTheme($theme, $group);

            $this->checkThemeStatus($currentTheme);

            if ($currentTheme) {
                View::addNamespace('theme', $currentTheme->views);

                return true;
            }

            throw new ThemeManagerException("Configured default theme \"{$group}:{$theme}\" could not found.");
        }

        return false;
    }

    /**
     * Check theme is active
     *
     * @param object $currentTheme
     */
    private function checkThemeStatus(object $currentTheme)
    {
        if (!$currentTheme->status) {
            throw new ThemeManagerException("Theme disabled.");
        }
    }
}

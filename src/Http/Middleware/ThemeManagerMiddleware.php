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

        if (!$currentTheme->status) {
            throw new ThemeManagerException("Theme disabled.");
        }

        // check theme forced by service
        if ($currentTheme) {
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

                if (!$currentTheme->status) {
                    throw new ThemeManagerException("Theme disabled.");
                }

                if ($currentTheme) {
                    if ($restrictGroup && $currentTheme->group !== $restrictGroup) {
                        throw new ThemeManagerException("This route resticted only theme group:
                            \"{$restrictGroup}\", provided group: \"{$currentTheme->group}\"");
                    }

                    View::addNamespace('theme', $currentTheme->views);

                    return $next($request);
                }

                if ($defaultTheme == null) {
                    throw new ThemeManagerException("Configured middleware theme \"{$group}:{$theme}\" could not found.");
                }
            }

            // check default theme
            if ($defaultTheme) {
                if (is_string($defaultTheme)) {
                    $_defaultTheme = explode(':', $defaultTheme);

                    $group = $_defaultTheme[0];
                    $theme = $_defaultTheme[1];
                } else if ($group && is_array($defaultTheme)) {
                    foreach ($defaultTheme as $themePair) {
                        if (Str::startsWith($themePair, "{$group}:")) {
                            $_defaultTheme = explode(':', $themePair);

                            $theme = $_defaultTheme[1];

                            break;
                        }
                    }
                }

                if ($theme && $group) {
                    $currentTheme = ThemeManager::setTheme($theme, $group);

                    if (!$currentTheme->status) {
                        throw new ThemeManagerException("Theme disabled.");
                    }

                    if ($currentTheme) {
                        View::addNamespace('theme', $currentTheme->views);

                        return $next($request);
                    }

                    throw new ThemeManagerException("Configured default theme
                        \"{$group}:{$theme}\" could not found.");
                }
            }
        }

        throw new ThemeManagerException("Requested theme could not found.");
    }
}

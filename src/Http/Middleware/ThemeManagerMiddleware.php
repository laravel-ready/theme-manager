<?php

namespace LaravelReady\ThemeManager\Http\Middleware;

use Closure;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

use LaravelReady\ThemeManager\Exceptions\Theme\ThemeManagerException;
use LaravelReady\ThemeManager\Support\ThemeSupport;
use LaravelReady\ThemeManager\Services\ThemeManager;

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
                View::addNamespace('theme', $currentTheme->views);

                return $next($request);
            } else {
                $args = ThemeSupport::handleArguments($arg1, $arg2, $arg3);
                $theme = $args['theme'] ?? null;
                $group = $args['group'] ?? null;
                $restrictGroup = $args['restrict_group'] ?? null;

                // check middleware theme
                if ($theme && $group) {
                    $currentTheme = ThemeManager::setTheme($theme, $group);

                    if ($currentTheme) {
                        if ($restrictGroup && $currentTheme->group !== $restrictGroup) {
                            throw new ThemeManagerException("This route resticted only theme group:
                                \"{$restrictGroup}\", provided group: \"{$currentTheme->group}\"");
                        }

                        View::addNamespace('theme', $currentTheme->views);

                        return $next($request);
                    }

                    throw new ThemeManagerException("Configured middleware theme \"{$group}:{$theme}\" could not found.");
                } else {
                    // check default theme
                    $defaultTheme = Config::get('theme-manager.default_theme');
                    $defaultGroup = Config::get('theme-manager.default_group');

                    if ($defaultTheme && $defaultGroup) {
                        $currentTheme = ThemeManager::setTheme($defaultTheme, $defaultGroup);

                        if ($currentTheme) {
                            View::addNamespace('theme', $currentTheme->views);

                            return $next($request);
                        }

                        throw new ThemeManagerException("Configured default theme
                            \"{$defaultGroup}:{$defaultTheme}\" could not found.");
                    }
                }
            }

            throw new ThemeManagerException("Requested theme could not found.");
    }
}

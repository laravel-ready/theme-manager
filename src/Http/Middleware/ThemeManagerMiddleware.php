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

            $args = ThemeSupport::handleArguments($arg1, $arg2, $arg3);
            $theme = $args['theme'] ?? null;
            $group = $args['group'] ?? null;
            $restrictGroup = $args['restrict_group'] ?? null;

            if ($theme && $group) {
                $currentTheme = ThemeManager::setTheme($theme, $group);
            }

            $currentTheme = Config::get('theme-manager.current_theme');

            if ($currentTheme) {
                if ($restrictGroup && $currentTheme->group !== $restrictGroup) {
                    throw new ThemeManagerException("This route resticted only theme group: \"{$restrictGroup}\", provided group: \"{$currentTheme->group}\"");
                }

                View::addNamespace('theme', $currentTheme->views);

                return $next($request);
            }

            throw new ThemeManagerException("Requested theme \"{$group}:{$theme}\" could not found.");
    }
}

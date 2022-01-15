<?php

namespace LaravelReady\ThemeManager\Http\Middleware;

use Closure;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

use LaravelReady\ThemeManager\Exceptions\Theme\ThemeManagerException;

class ThemeManagerMiddleware
{
    public function handle(Request $request, Closure $next, string $themeGroup = null)
    {
        $themeAlias = Config::get('theme-manager.active_theme_alias');

        if ($themeAlias) {
            $cacheKey = 'theme-manager.themes';

            if (!Cache::has($cacheKey) || !Cache::get($cacheKey)) {
                app('theme-manager')->scanThemes(true);
            }

            $theme = Config::get('theme-manager.current_theme');

            if ($theme) {
                if ($themeGroup && $theme->group !== trim($themeGroup)){
                    throw new ThemeManagerException("Requested theme group and target theme group are not match.
                        Please use same theme group with middleware.
                        Required group: \"{$themeGroup}\", provided group: \"{$theme->group}\"");
                }

                if (file_exists($theme->views)) {
                    View::addNamespace('theme', $theme->views);

                    return $next($request);
                }
            }

            throw new ThemeManagerException("Requested theme \"{$themeAlias}\" could not found.");
        }

        throw new ThemeManagerException('Theme is not provided.');
    }
}

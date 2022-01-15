<?php

namespace LaravelReady\ThemeManager;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

use LaravelReady\ThemeManager\Services\ThemeManager;
use LaravelReady\ThemeManager\Directives\AssetDirectives;

use LaravelReady\ThemeManager\Services\CustomBladeCompiler;
use LaravelReady\ThemeManager\Http\Middleware\ThemeManagerMiddleware;

final class ThemeServiceProvider extends BaseServiceProvider
{
    public function boot(Router $router): void
    {
        $this->bootPublishes();

        $this->registerDirectives();

        $this->loadMiddlewares($router);

        if (!App::environment('production') && config('theme-manager.disable_view_cache')) {
            Artisan::call('view:clear');
        }
    }

    public function register(): void
    {
        $this->registerConfigs();

        /*--------------------------------------------------------------------------
        | Register theme manager service
        |--------------------------------------------------------------------------*/

        $this->app->singleton('theme-manager', function () {
            return new ThemeManager();
        });

        /*--------------------------------------------------------------------------
        | Bind custom blade compiler
        |--------------------------------------------------------------------------*/

        if (!App::environment('production') && Config::get('theme-manager.disable_view_cache')) {
            $this->app->singleton('blade.compiler', function ($app) {
                return new CustomBladeCompiler($app['files'], "{$app['path.storage']}//views");
            });
        }
    }

    protected function bootPublishes(): void
    {
        $this->publishes([
            __DIR__ . '/../config/theme-manager.php' => $this->app->configPath('theme-manager.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations')
        ], 'theme-manager-migrations');
    }

    protected function registerConfigs(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/theme-manager.php', 'theme-manager');
    }

    /**
     * Register blade directives
     */
    private function registerDirectives(): void
    {
        foreach ((new AssetDirectives())->directives as $name => $function) {
            Blade::directive($name, $function);
        }
    }

    private function loadMiddlewares($router): void
    {
        $router->aliasMiddleware('theme-manager', ThemeManagerMiddleware::class);
    }
}

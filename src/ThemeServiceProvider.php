<?php

namespace LaravelReady\ThemeManager;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

use LaravelReady\ThemeManager\Services\ThemeManager;
use LaravelReady\ThemeManager\Directives\AssetDirectives;
use LaravelReady\ThemeManager\Services\CustomBladeCompiler;
use LaravelReady\ThemeManager\Http\Middleware\ThemeManagerMiddleware;

use LaravelReady\ThemeManager\Console\Commands\Theme\ListCommand;
use LaravelReady\ThemeManager\Console\Commands\Theme\CreateCommand;
use LaravelReady\ThemeManager\Console\Commands\Theme\DeleteCommand;
use LaravelReady\ThemeManager\Console\Commands\Theme\StatusCommand;

final class ThemeServiceProvider extends BaseServiceProvider
{
    public function boot(Router $router): void
    {
        $this->bootPublishes();

        $this->registerDirectives();

        $this->loadMiddlewares($router);

        $this->loadCommands();

        $this->applyPreConfigs();

        if (!App::environment('production') && config('theme-manager.disable_view_cache')) {
            Artisan::call('view:clear');
        }
    }

    public function register(): void
    {
        $this->registerConfigs();

        // register theme manager service
        $this->app->singleton('theme-manager', function () {
            return new ThemeManager();
        });

        // bind custom blade compiler
        if (!App::environment('production') && Config::get('theme-manager.disable_view_cache')) {
            $this->app->singleton('blade.compiler', function ($app) {
                return new CustomBladeCompiler($app['files'], "{$app['path.storage']}//views");
            });
        }
    }

    /**
     * Boot publishes
     */
    private function bootPublishes(): void
    {
        $this->publishes([
            __DIR__ . '/../config/theme-manager.php' => $this->app->configPath('theme-manager.php'),
        ], 'theme-manager-config');

        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations')
        ], 'theme-manager-migrations');
    }

    /**
     * Regsiter pacakge configs
     */
    private function registerConfigs(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/theme-manager.php', 'theme-manager');
    }

    /**
     * Apply pre configs
     */
    private function applyPreConfigs()
    {
        $mergeConfigs = Cache::get('theme-manager.preconfigs');

        if (is_array($mergeConfigs)) {
            foreach ($mergeConfigs as $key => $value) {
                $config = Config::get("theme-manager.{$key}");

                if (is_array($config) || is_array($value) && (!is_array($config) && $config !== null)) {
                    Config::set("theme-manager.{$key}", array_merge([$config], $value));
                } else {
                    Config::set("theme-manager.{$key}", $value);
                }
            }
        }
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

    /**
     * Load ThemeManagerMiddleware
     *
     * @param Router $router
     */
    private function loadMiddlewares(Router $router): void
    {
        $router->aliasMiddleware('theme-manager', ThemeManagerMiddleware::class);
    }

    /**
     * Load package commands
     */
    private function loadCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ListCommand::class,
                CreateCommand::class,
                DeleteCommand::class,
                StatusCommand::class
            ]);
        }
    }
}

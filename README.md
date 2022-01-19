# Laravel Theme Manager

![EgoistDeveloper Laravel Theme Manager](https://preview.dragon-code.pro/EgoistDeveloper/Laravel-Theme-Manager.svg?brand=laravel)

[![Stable Version][badge_stable]][link_packagist]
[![Unstable Version][badge_unstable]][link_packagist]
[![Total Downloads][badge_downloads]][link_packagist]
[![License][badge_license]][link_license]


## 🔥 Concept

Dynamic theme manager brings theme support to Laravel projects. Theme Manager manages multiple theme at same time and you won't lose build-in Laravel features. This package is uses custom middleware for overwriting view path with selected theme.

Add `theme-manager` middleware alias to your `web` or `custom` route chain. Then Theme Manager can manipulate the views. Also this package uses custom Blade Compiler and if you are try to overwrite the Blade Compiler won't work anymore.

### Themes folder

In your root folder create `themes` folder then add your themes to own `group` folder. Also you can change `themes` folder name with package configs.

Theme folder structure must be like this:


```
.
└── src
    ├── app
    ├── public
    ├── ...
    ├── {themes}
        ├── web
        │    ├── my-awesome-theme
        │    ├── your-awesome-theme
        │    ├── ...
        ├── admin
        │    ├── banana
        │    ├── cerry
        │    ├── apple
        │    ├── orange
        │    ├── ...
        ├── other-group
        │    ├── other-theme
        │    ├── other-other-theme
        │    ├── ...
        ├── ...
```

In this case `web`, `admin` and `other-group` is theme group. We use groups for specific purposes. Then we can add specific themes to own group folder.


You should publish this package configs, see installation part.


## ⚡ Installation

⚠️ This package compatible only Laravel 8 and above.

To get the latest version of `Laravel Theme Manager`, simply require the project using [Composer](https://getcomposer.org):

```bash
$ composer require laravel-ready/theme-manager
```

Or manually update `require` block of `composer.json` and run `composer update`.

```json
{
    "require": {
        "laravel-ready/theme-manager": "^1.0"
    }
}
```

### Publish configs

`php artisan vendor:publish --tag=theme-manager-config` and you can find in `config` folder.


### Middleware

Add `theme-manager` to your base route in *{route}.php* or `app/Providers/RouteServiceProvider.php`

#### in *routes/web.php*

```php
Route::prefix('/')->middleware(['theme-manager', 'another-mw'])->group(function (){
    Route::get('', [LandingController::class, 'index'])->name('home');

    Route::get('my-page', [LandingController::class, 'anyPage'])->name('my-page');
});
```

#### in *RouteServiceProvider.php*

```php
public function boot()
{
    $this->configureRateLimiting();

    $this->routes(function () {
        ...

        Route::middleware(['web', 'theme-manager'])
            ->namespace("{$this->namespace}\\Web")
            ->group(base_path('routes/web.php'));

        Route::middleware(['web', 'theme-manager'])
            ->namespace("{$this->namespace}\\Admin")
            ->group(base_path('routes/admin.php'));

    });
}
```

#### *Theme groups* in middleware

Theme Manager works with `theme` and `group` pair and also you can restrict with the route specific theme groups.

| Parameter | Description | Usage |
| --------- | ----------- | ---------- |
| **theme** | Specify theme alias | `theme-manager:theme=my-theme` |
| **group** | Specify group alias | `theme-manager:group=admin` |
| **restrict_group** | Restrict route with target group | `theme-manager:restrict_group=admin` |

Combined usage: `theme-manager:theme=my-theme, group=admin, restrict_group=admin`

⚠️ Parameter ordering does not matter.

## 🚀 Usage

Theme loading and other options are depends to your logic. You can use in controller, middleware etc.

### Scan installed themes

`app('theme-manager')->scanThemes();` returns list of theme groups and their themes.

Theme Manager, after themes scanned themes adds to cache. When you add new theme you should rescan the themes. Just pass `true` as parameter or use `app('theme-manager')->reScanThemes();` method.

### Get current theme

`app('theme-manager')->getCurrentTheme();` returns current theme details.

### Get target theme

`app('theme-manager')->getTheme();` returns requested theme details.

### Set current theme

`setTheme` is required two parameters. Target theme and theme group.

`app('theme-manager')->setTheme('my-theme', 'theme-group')`

### Using directly ThemeManager service

Add `use LaravelReady\ThemeManager\Services\ThemeManager;` namespace then call same methods `$themeManager->...()`.

### Calling theme views

We can call regular views with `return View('welcome')`. If you want to call the theme view use `theme::` alias like `return View('theme::welcome')`.

`theme::` alias is universal gateway for Theme Manager. After you use `setTheme` method Theme Manager finds theme views then renders.

Also you  can't use like `theme::theme-name...`. You can only define themes with `ThemeManager` service class and middleware.

## ⚓ Blade Directives

### Asset directives

| Directive | Description | Parameters |
| --------- | ----------- | ---------- |
| **@asset**     | Get theme asset URL | `0`: Asset path, `1`: Print theme version number (default `true`) |
| **@assetLoad** | Get theme asset content as string | `0`: Asset path, `1`: Fallback asset (default `null`) |
| **@svg**       | Get SVG content as string    | `0`: SVG file name, `1`: Class name (default `null`), `2`: CSS style (default `null`) |


#### Usage:

- **@asset**
  - `@asset('css/base.css')`
  - `@asset('css/base.css', true|false)`
  - `@asset('js/app.js')`
  - `@asset('images/user/avatar-01.jpg')`
  - `@asset('favicons/favion.ico')`
- **@assetLoad**
  - `@assetLoad('css/base.css')`
  - `@assetLoad('html/any-template.html')`
  - `@assetLoad('images/svg/sunshine.svg', 'images/svg/moonshine.svg')`
- **@svg**
  - `@svg('chevron-left')`
  - `@svg('chevron-right', 'mx-auto')`
  - `@svg('chevron-down', 'mx-auto', 'fill: green; top: 20px; position: relative;')`


The above asset paths `css`, `js`, `html` are not reserved or any custom paths are depends to your theme `webpack.mix.js` configs and design approach.

⚠️ Published theme specific assets must be in `project-folder/src/public/themes/{group-alias}/{theme-alias}/` folder because Theme Manager uses this path for directives.



[badge_downloads]:      https://img.shields.io/packagist/dt/laravel-ready/theme-manager.svg?style=flat-square

[badge_license]:        https://img.shields.io/packagist/l/laravel-ready/theme-manager.svg?style=flat-square

[badge_stable]:         https://img.shields.io/github/v/release/laravel-ready/theme-manager?label=stable&style=flat-square

[badge_unstable]:       https://img.shields.io/badge/unstable-dev--main-orange?style=flat-square

[link_license]:         LICENSE

[link_packagist]:       https://packagist.org/packages/laravel-ready/theme-manager


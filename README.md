# Laravel Theme Manager

Theme manager for Laravel

[![Stable Version][badge_stable]][link_packagist]
[![Unstable Version][badge_unstable]][link_packagist]
[![Total Downloads][badge_downloads]][link_packagist]
[![License][badge_license]][link_license]

## Table of contents

* [Installation](#installation)

## ⚡ Installation

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

### Middleware

Add `theme-manager` middleware alias to your `web` or `custom` route chain. Then Theme Manager can manipulate the views. Also this package uses custom Blade Compiler and if you are overwrite the Blade Compiler might not work anymore.


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


The above asset paths `css`, `js`, `html` are not reserved or any custom paths are depends to your theme `webpack.mix.js` and design approach.


[badge_downloads]:      https://img.shields.io/packagist/dt/laravel-ready/theme-manager.svg?style=flat-square

[badge_license]:        https://img.shields.io/packagist/l/laravel-ready/theme-manager.svg?style=flat-square

[badge_stable]:         https://img.shields.io/github/v/release/laravel-ready/theme-manager?label=stable&style=flat-square

[badge_unstable]:       https://img.shields.io/badge/unstable-dev--main-orange?style=flat-square

[link_license]:         LICENSE

[link_packagist]:       https://packagist.org/packages/laravel-ready/theme-manager


<?php

namespace LaravelReady\ThemeManager\Facades;

use Illuminate\Support\Facades\Facade;

class ThemeManager extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'theme-manager';
    }
}

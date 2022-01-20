<?php

namespace LaravelReady\ThemeManager\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\View\Compilers\BladeCompiler;

class CustomBladeCompiler extends BladeCompiler
{
    /**
     * Overwrite of isExpired function
     *
     * This overwrite process is required for disabling view caching
     *
     * @param string $path
     *
     * @return bool
     */
    public function isExpired($path)
    {
        if (!Config::get('view.cache')) {
            return true;
        }

        return parent::isExpired($path);
    }
}

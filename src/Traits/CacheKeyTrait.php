<?php

namespace LaravelReady\ThemeManager\Traits;


trait CacheKeyTrait
{
    public static $themesCacheKey = 'theme-manager.themes';

    public static $themesRootFolderCacheKey = 'theme-manager.themes_root_folder';
}

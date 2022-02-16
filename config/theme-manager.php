<?php

return [

    /**
     * Default vendor/theme pair
     *
     * When target theme is not found loads default theme
     *
     * If value is null throws exception eventually
     *
     * Accepts string or array string
     */
    'default_theme' => null,

    /**
     * Themes root folder required for loading views, assets etc.
     */
    'themes_root_folder' => 'themes',

    /**
     * Disable blade view caching
     *
     * This option won't run on production
     */
    'disable_view_cache' => true,
];

<?php

return [

    /**
     * Default theme alias
     *
     * When target theme is not found loads default theme
     *
     * If value is null throws exception eventually
     */
    'default_theme' => null,

    /**
     * Default group alias
     *
     * Required with "default_theme"
     */
    'default_group' => null,

    /**
     * Themes root folder required for loading blade views etc.
     */
    'themes_root_folder' => 'themes',

    /**
     * Disable blade view caching
     *
     * This option won't run on production
     */
    'disable_view_cache' => true,

];

<?php

namespace LaravelReady\ThemeManager\Support;

class ThemeSupport
{
    /**
     * Extract blade directive arguments as array
     *
     * @param string $args
     *
     * @return array
     */
    public static function getDirectiveArguments(string $args): array
    {
        return explode(',', str_replace(['(', ')', ' ', "'"], '', $args));
    }

    /**
     * Return absolute bool value
     *
     * Input is not isset = true
     * Input isset and 'true' = true
     * Input isset and not 'false' = true
     *
     * @param mixed $args
     * @param int $index
     *
     * @return bool
     */
    public static function absoluteTrue(mixed $args, int $index): bool
    {
        return !isset($args[$index]) ||
            (isset($args[$index]) && $args[$index] === 'true') ||
            (isset($args[$index]) && $args[$index] !== 'false');
    }
}

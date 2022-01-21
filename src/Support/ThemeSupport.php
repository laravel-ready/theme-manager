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

    /**
     * Hand mixed arguments
     *
     * @param string $arg1
     * @param string $arg2
     * @param string $arg3
     */
    public static function handleArguments(string $arg1 = null, string $arg2 = null, string $arg3 = null)
    {
        $args = [];

        for ($i = 1; $i < 4; $i++) {
            $varName = "arg{$i}";

            if ($$varName) {
                $parameters = explode('=', $$varName);

                if (count($parameters) == 2) {
                    $args[trim($parameters[0])] = trim($parameters[1]);
                }
            }
        }

        return $args;
    }

    /**
     * Pretty preint JSON content
     *
     * @param mixed $content
     *
     * @return string
     */
    public static function prettyJson(mixed $content): string
    {
        return json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

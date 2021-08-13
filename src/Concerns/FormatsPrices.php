<?php

namespace Whitecube\Price\Concerns;

use Whitecube\Price\Formatting\Formatter;
use Whitecube\Price\Formatting\CustomFormatter;

trait FormatsPrices
{
    /**
     * The defined custom formatters.
     *
     * @var array
     */
    static protected $formatters = [];

    /**
     * Formats the given monetary value into the application's currently preferred format.
     *
     * @param array $arguments
     * @return string
     */
    static public function format(...$arguments)
    {
        return static::callFormatter(null, ...$arguments);
    }

    /**
     * Formats the given monetary value into the application's currently preferred format.
     *
     * @param null|string $name
     * @param array $arguments
     * @return string
     */
    static protected function callFormatter($name, ...$arguments)
    {
        return static::getAssignedFormatter($name)->call($arguments);
    }

    /**
     * Formats the given monetary value using the package's default formatter.
     * This static method is hardcoded in order to prevent overwriting.
     *
     * @param string $value
     * @param null|string $locale
     * @return string
     */
    static public function formatDefault($value, $locale = null)
    {
        return static::getDefaultFormatter()->call($arguments);
    }

    /**
     * Formats the given monetary value using the package's default formatter.
     * This static method is hardcoded in order to prevent overwriting.
     *
     * @param string $value
     * @param null|string $locale
     * @return \Whitecube\Price\CustomFormatter
     */
    static public function formatUsing(callable $closure) : CustomFormatter
    {
        $instance = new CustomFormatter($closure);

        static::$formatters[] = $instance;

        return $instance;
    }

    /**
     * Returns the correct formatter for the requested context
     *
     * @param null|string $name
     * @return \Whitecube\Price\Formatter
     */
    static protected function getAssignedFormatter($name = null) : Formatter
    {
        foreach (static::$formatters as $formatter) {
            if ($formatter->is($name)) return $formatter;
        }

        return static::getDefaultFormatter();
    }

    /**
     * Returns the package's default formatter
     *
     * @return \Whitecube\Price\Formatter
     */
    static protected function getDefaultFormatter() : Formatter
    {
        return new Formatter();
    }
}

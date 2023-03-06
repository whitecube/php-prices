<?php

namespace Whitecube\Price\Concerns;

use Whitecube\Price\Formatting\Formatter;
use Whitecube\Price\Formatting\CustomFormatter;
use Brick\Money\AbstractMoney;
use Whitecube\Price\Price;

trait FormatsPrices
{
    /**
     * The defined custom formatters.
     */
    static protected array $formatters = [];

    /**
     * Formats the given monetary value into the application's currently preferred format.
     */
    static public function format(...$arguments): ?string
    {
        return static::callFormatter(null, ...$arguments);
    }

    /**
     * Formats the given monetary value into the application's currently preferred format.
     */
    static protected function callFormatter(?string $name, ...$arguments): ?string
    {
        return static::getAssignedFormatter($name)->call($arguments);
    }

    /**
     * Formats the given monetary value using the package's default formatter.
     * This static method is hardcoded in order to prevent overwriting.
     */
    static public function formatDefault(AbstractMoney|Price $value, ?string $locale = null): string
    {
        return static::getDefaultFormatter()->call([$value, $locale]);
    }

    /**
     * Formats the given monetary value using the package's default formatter.
     * This static method is hardcoded in order to prevent overwriting.
     */
    static public function formatUsing(string|callable|CustomFormatter $formatter): CustomFormatter
    {
        if(is_string($formatter) && is_a($formatter, CustomFormatter::class, true)) {
            $instance = new $formatter;
        } elseif (is_a($formatter, CustomFormatter::class)) {
            $instance = $formatter;
        } elseif (is_callable($formatter)) {
            $instance = new CustomFormatter($formatter);
        }

        static::$formatters[] = $instance;

        return $instance;
    }

    /**
     * Returns the correct formatter for the requested context
     */
    static protected function getAssignedFormatter(?string $name = null): Formatter
    {
        foreach (static::$formatters as $formatter) {
            if ($formatter->is($name)) return $formatter;
        }

        return static::getDefaultFormatter();
    }

    /**
     * Returns the package's default formatter
     */
    static protected function getDefaultFormatter(): Formatter
    {
        return new Formatter();
    }

    /**
     * Unsets all the previously defined custom formatters.
     */
    static public function forgetAllFormatters(): void
    {
        static::$formatters = [];
    }
}

<?php

namespace Whitecube\Price\Formatting;

use NumberFormatter;
use Brick\Money\Money;
use Whitecube\Price\Vat;
use Whitecube\Price\Price;

class Formatter
{
    /**
     * Check if this formatter has the provided name
     *
     * @param null|string $name
     * @return bool
     */
    public function is($name = null)
    {
        return is_null($name);
    }

    /**
     * Run the formatter using the provided arguments
     *
     * @param array $arguments
     * @return null|string
     */
    public function call(array $arguments) : ?string
    {
        [$value, $locale] = (count($arguments) > 2)
            ? array_slice($arguments, 0, 2)
            : array_pad($arguments, 2, null);

        if(! is_a($value = $this->toMoney($value), Money::class)) {
            return null;
        }

        if(! $locale) {
            $locale = locale_get_default();
        }

        return $this->format($value, $locale);
    }

    /**
     * Get the Money instance from the provided value.
     *
     * @param mixed $value
     * @return null|\Brick\Money\Money
     */
    protected function toMoney($value) : ?Money
    {
        if(is_a($value, Money::class)) {
            return $value;
        }

        if(is_a($value, Price::class)) {
            return $value->inclusive();
        }

        if (is_a($value, Vat::class)) {
            return $value->money();
        }

        return null;
    }

    /**
     * Transform the money instance into a human-readable string
     *
     * @param \Brick\Money\Money $value
     * @return string $locale
     * @return string
     */
    protected function format(Money $value, string $locale) : string
    {
        $currency = $value->getCurrency()->getCurrencyCode();
        $value = $value->getAmount()->toFloat();

        $fmt = new NumberFormatter($locale, NumberFormatter::CURRENCY);

        return $fmt->formatCurrency($value, $currency);
    }
}

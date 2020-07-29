<?php

namespace Whitecube\Price;

use Money\Money;
use Money\Currency;

trait ParsesPrices
{
    /**
     * Return the price's base value
     *
     * @param mixed $value
     * @param int $units
     * @param null|string $currency
     * @return \Whitecube\Price\Price
     */
    static public function parseCurrency($value, $units = 1, $currency = null)
    {
        $parser = new Parser($value);

        $value = new Money(
            $parser->extractValue(),
            new Currency($currency ?? $parser->extractCurrency())
        );

        return new Price($value, $units);
    }

    /**
     * Try to call a specific currency parser
     *
     * @param string $method
     * @param array $arguments
     * @return \Whitecube\Price\Price
     * @throws \BadMethodCallException
     */
    static public function __callStatic($method, $arguments)
    {
        if(strpos($method, 'parse') === 0) {
            $currency = strtoupper(substr($method, 5));

            return static::parseCurrency($arguments[0], $arguments[1] ?? 1, $currency);
        }

        throw new \BadMethodCallException('Call to undefined static method ' . static::class . '::' . $method . '.');
    }
}

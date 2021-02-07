<?php

namespace Whitecube\Price\Concerns;

use Whitecube\Price\Price;
use Whitecube\Price\Parser;
use Brick\Money\Money;

trait ParsesPrices
{
    /**
     * Create an instance from a major-value string, with 
     * or without defined currency (in which case it guesses).
     *
     * @param string $value
     * @param null|mixed $currency
     * @param int $units
     * @return \Whitecube\Price\Price
     */
    static public function parse($value, $currency = null, $units = 1)
    {
        $parser = new Parser($value);

        $value = Money::ofMinor(
            $parser->extractValue(),
            $currency ?? $parser->extractCurrency()
        );

        return new Price($value, $units);
    }
}

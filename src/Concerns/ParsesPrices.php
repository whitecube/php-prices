<?php

namespace Whitecube\Price\Concerns;

use Whitecube\Price\Price;
use Whitecube\Price\Parser;
use Brick\Money\Money;
use Brick\Money\Currency;

trait ParsesPrices
{
    /**
     * Create an instance from a major-value string, with 
     * or without defined currency (in which case it guesses).
     */
    static public function parse(string $value, null|Currency|string|int $currency = null, int $units = 1): Price
    {
        $parser = new Parser($value);

        $value = Money::ofMinor(
            $parser->extractValue(),
            $currency ?? $parser->extractCurrency()
        );

        return new Price($value, $units);
    }
}

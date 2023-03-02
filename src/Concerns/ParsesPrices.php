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
     */
    static public function parse(string $value, mixed $currency = null, int $units = 1): Price
    {
        $parser = new Parser($value);

        $value = Money::ofMinor(
            $parser->extractValue(),
            $currency ?? $parser->extractCurrency()
        );

        return new Price($value, $units);
    }
}

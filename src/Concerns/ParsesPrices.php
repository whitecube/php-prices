<?php

namespace Whitecube\Price\Concerns;

use Whitecube\Price\Price;
use Whitecube\Price\Parser;
use Brick\Money\Money;

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
    static public function parse($value, $currency = null)
    {
        $parser = new Parser($value);

        $value = Money::ofMinor(
            $parser->extractValue(),
            $currency ?? $parser->extractCurrency()
        );

        return new Price($value);
    }
}

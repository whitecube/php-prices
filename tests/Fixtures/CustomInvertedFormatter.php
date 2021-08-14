<?php

namespace Tests\Fixtures;

use Brick\Money\Money;
use Whitecube\Price\Price;
use Whitecube\Price\Formatting\CustomFormatter;

class CustomInvertedFormatter extends CustomFormatter
{
    /**
     * Run the formatter using the provided arguments
     *
     * @param array $arguments
     * @return null|string
     */
    public function call(array $arguments) : ?string
    {
        [$value, $locale] = $this->getMoneyAndLocale($arguments);

        if(! is_a($value, Money::class)) {
            return null;
        }

        return Price::formatDefault($value->multipliedBy(-1), $locale);
    }
}

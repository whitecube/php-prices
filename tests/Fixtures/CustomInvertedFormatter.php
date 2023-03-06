<?php

namespace Tests\Fixtures;

use Whitecube\Price\Price;
use Whitecube\Price\Formatting\CustomFormatter;
use Brick\Money\AbstractMoney;

class CustomInvertedFormatter extends CustomFormatter
{
    /**
     * Run the formatter using the provided arguments
     */
    public function call(array $arguments): ?string
    {
        [$value, $locale] = $this->getMoneyAndLocale($arguments);

        if(! is_a($value, AbstractMoney::class)) {
            return null;
        }

        return Price::formatDefault($value->multipliedBy(-1), $locale);
    }
}

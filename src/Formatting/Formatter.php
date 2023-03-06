<?php

namespace Whitecube\Price\Formatting;

use NumberFormatter;
use Brick\Money\AbstractMoney;
use Whitecube\Price\Vat;
use Whitecube\Price\Price;

class Formatter
{
    /**
     * Check if this formatter has the provided name
     */
    public function is(?string $name = null): bool
    {
        return is_null($name);
    }

    /**
     * Run the formatter using the provided arguments
     */
    public function call(array $arguments) : ?string
    {
        [$value, $locale] = $this->getMoneyAndLocale($arguments);

        if(! is_a($value, AbstractMoney::class)) {
            return null;
        }

        return $this->format($value, $locale);
    }

    /**
     * Extract the Money and locale arguments from the provided arguments array.
     */
    protected function getMoneyAndLocale(array $arguments, int $moneyIndex = 0, int $localeIndex = 1) : array
    {
        if($money = $arguments[$moneyIndex] ?? null) {
            $money = $this->toMoney($money);
        }

        if(! ($locale = $arguments[$localeIndex] ?? null)) {
            $locale = locale_get_default();
        }

        return [$money, $locale];
    }

    /**
     * Get the Money instance from the provided value.
     */
    protected function toMoney(Price|AbstractMoney|Vat $value) : AbstractMoney
    {
        if(is_a($value, AbstractMoney::class)) {
            return $value;
        }

        if(is_a($value, Price::class)) {
            return $value->inclusive();
        }

        if (is_a($value, Vat::class)) {
            return $value->money();
        }
    }

    /**
     * Transform the money instance into a human-readable string
     */
    protected function format(AbstractMoney $value, string $locale) : string
    {
        $currency = $value->getCurrency()->getCurrencyCode();
        $value = $value->getAmount()->toFloat();

        $fmt = new NumberFormatter($locale, NumberFormatter::CURRENCY);

        return $fmt->formatCurrency($value, $currency);
    }
}

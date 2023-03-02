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
        [$value, $locale] = $this->getMoneyAndLocale($arguments);

        if(! is_a($value, AbstractMoney::class)) {
            return null;
        }

        return $this->format($value, $locale);
    }

    /**
     * Extract the Money and locale arguments from the provided arguments array.
     *
     * @param array $arguments
     * @param int $moneyIndex
     * @param int $localeIndex
     * @return array
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
     *
     * @param mixed $value
     * @return null|\Brick\Money\Money
     */
    protected function toMoney($value) : ?AbstractMoney
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

        return null;
    }

    /**
     * Transform the money instance into a human-readable string
     *
     * @param \Brick\Money\Money $value
     * @return string $locale
     * @return string
     */
    protected function format(AbstractMoney $value, string $locale) : string
    {
        $currency = $value->getCurrency()->getCurrencyCode();
        $value = $value->getAmount()->toFloat();

        $fmt = new NumberFormatter($locale, NumberFormatter::CURRENCY);

        return $fmt->formatCurrency($value, $currency);
    }
}

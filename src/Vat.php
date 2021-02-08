<?php

namespace Whitecube\Price;

use Brick\Money\Money;
use Brick\Money\Currency;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

class Vat
{
    /**
     * The VAT's percentage value
     *
     * @var \Brick\Money\Currency
     */
    private $currency;

    /**
     * The VAT's percentage value
     *
     * @var \Brick\Math\BigDecimal
     */
    protected $percentage;

    /**
     * The applied values
     *
     * @var null|array
     */
    protected $applied = null;

    /**
     * Create a new VAT value
     *
     * @param mixed $value
     * @param Brick\Money\Currency $currency
     * @return void
     */
    public function __construct($value, Currency $currency)
    {
        $this->currency = $currency;
        $this->percentage = BigDecimal::of($value);
    }

    /**
     * Get the VAT's percentage float value
     *
     * @return float
     */
    public function getPercentage()
    {
        return $this->percentage->toFloat();
    }

    /**
     * Get the VAT's Money value
     *
     * @param bool $perUnit
     * @return \Brick\Money\Money
     */
    public function getAmount($perUnit = false)
    {
        $key = $perUnit ? 'unit' : 'all';

        if(! $this->applied || ! ($this->applied[$key] ?? null)) {
            return Money::zero($this->currency);
        }

        return $this->applied[$key];
    }

    /**
     * Compute the VAT's values
     *
     * @param \Brick\Money\Money $exclusive
     * @param \Whitecube\Price\Price $price
     * @return void
     */
    public function apply(Money $exclusive, Price $price)
    {
        $multiplier = $this->percentage->dividedBy(100, $this->percentage->getScale() + 2, RoundingMode::UP);

        $amount = $exclusive->multipliedBy($multiplier, Price::getRounding('vat'));

        $this->applied = [
            'all' => $amount,
            'unit' => $price->perUnit($amount)
        ];
    }
}

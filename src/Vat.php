<?php

namespace Whitecube\Price;

use Brick\Money\Money;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

class Vat
{
    /**
     * The price this VAT object belongsTo
     *
     * @var \Whitecube\Price\Price
     */
    private $price;

    /**
     * The VAT's percentage value
     *
     * @var \Brick\Math\BigDecimal
     */
    protected $percentage;

    /**
     * Create a new VAT value
     *
     * @param mixed $value
     * @param \Whitecube\Price\Price $price
     * @return void
     */
    public function __construct($value, Price $price)
    {
        $this->price = $price;
        $this->percentage = BigDecimal::of($value);
    }

    /**
     * Get the VAT's percentage float value
     *
     * @return float
     */
    public function percentage()
    {
        return $this->percentage->toFloat();
    }

    /**
     * Get the VAT's Money value
     *
     * @param bool $perUnit
     * @return \Brick\Money\Money
     */
    public function money($perUnit = false)
    {
        return $this->price->build()->vat($perUnit);
    }

    /**
     * Compute the VAT's values
     *
     * @param \Brick\Money\Money $exclusive
     * @return \Brick\Money\Money
     */
    public function apply(Money $exclusive)
    {
        $multiplier = $this->percentage->dividedBy(100, $this->percentage->getScale() + 2, RoundingMode::UP);

        return $exclusive->multipliedBy($multiplier, Price::getRounding('vat'));
    }
}

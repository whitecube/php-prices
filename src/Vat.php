<?php

namespace Whitecube\Price;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Brick\Money\AbstractMoney;
use Brick\Money\Money;
use Whitecube\Price\Price;
use Brick\Math\BigNumber;

class Vat
{
    /**
     * The price this VAT object belongsTo
     */
    private Price $price;

    /**
     * The VAT's percentage value
     */
    protected BigDecimal $percentage;

    /**
     * Create a new VAT value
     */
    public function __construct(BigNumber|int|float|string $value, Price $price)
    {
        $this->price = $price;
        $this->percentage = BigDecimal::of($value);
    }

    /**
     * Get the VAT's percentage float value
     */
    public function percentage(): float
    {
        return $this->percentage->toFloat();
    }

    /**
     * Get the VAT's Money value
     */
    public function money(bool $perUnit = false): AbstractMoney
    {
        return $this->price->build()->vat($perUnit);
    }

    /**
     * Compute the VAT's values
     */
    public function apply(AbstractMoney $exclusive): AbstractMoney
    {
        $multiplier = $this->percentage->dividedBy(100, $this->percentage->getScale() + 2, RoundingMode::UP);

        return $exclusive->multipliedBy($multiplier, Price::getRounding('vat'));
    }
}

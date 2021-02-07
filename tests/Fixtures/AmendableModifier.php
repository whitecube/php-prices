<?php

namespace Tests\Fixtures;

use Brick\Money\Money;
use Whitecube\Price\PriceAmendable;

class AmendableModifier implements PriceAmendable
{
    /**
     * Return the modifier type (tax, discount, other, ...)
     *
     * @return string
     */
    public function type() : string
    {
        return 'custom';
    }

    /**
     * Return the modifier's identification key
     *
     * @return null|string
     */
    public function key() : ?string
    {
        return 'foo-bar';
    }

    /**
     * Whether the modifier should be applied before the
     * VAT value has been computed.
     *
     * @return bool
     */
    public function isBeforeVat() : bool
    {
        return true;
    }

    /**
     * Apply the modifier on the given Money instance
     *
     * @param \Brick\Money\Money $value
     * @return null|\Brick\Money\Money
     */
    public function apply(Money $value) : ?Money
    {
        return $value->plus($value->multipliedBy(0.25));
    }
}
<?php

namespace Tests\Fixtures;

use Money\Money;
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
     * @param \Money\Money $value
     * @return null|\Money\Money
     */
    public function apply(Money $value) : ?Money
    {
        return $value->add($value->multiply(0.25));
    }
}
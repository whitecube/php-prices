<?php

namespace Whitecube\Price;

use Money\Money;

interface PriceAmendable
{
    /**
     * Return the modifier type (tax, discount, other, ...)
     *
     * @return string
     */
    public function type() : string;

    /**
     * Return the modifier's identification key
     *
     * @return null|string
     */
    public function key() : ?string;

    /**
     * Whether the modifier should be applied before the
     * VAT value has been computed.
     *
     * @return bool
     */
    public function isBeforeVat() : bool;

    /**
     * Apply the modifier on the given Money instance
     *
     * @param \Money\Money $value
     * @return null|\Money\Money
     */
    public function apply(Money $value) : ?Money;
}
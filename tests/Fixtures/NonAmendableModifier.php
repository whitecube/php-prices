<?php

namespace Tests\Fixtures;

use Brick\Money\Money;

class NonAmendableModifier
{
    /**
     * Return the modifier type (tax, discount, other, ...)
     *
     * @return string
     */
    public function type() : string
    {
        return 'should-not-appear';
    }

    /**
     * Return the modifier's identification key
     *
     * @return null|string
     */
    public function key() : ?string
    {
        return 'wrong';
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
        return $value->multipliedBy(10);
    }
}
<?php

namespace Tests\Fixtures;

use Brick\Money\Money;
use Whitecube\Price\PriceAmendable;

class CustomAmendableModifier implements PriceAmendable
{
    /**
     * The modifier's addition
     *
     * @var \Brick\Money\Money
     */
    protected $tax;

    /**
     * Create a new custom instance
     *
     * @param \Brick\Money\Money
     * @return void
     */
    public function __construct(Money $tax)
    {
        $this->tax = $tax;
    }

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
        return 'bar-foo';
    }

    /**
     * Whether the modifier should be applied before the
     * VAT value has been computed.
     *
     * @return bool
     */
    public function isBeforeVat() : bool
    {
        return false;
    }

    /**
     * Apply the modifier on the given Money instance
     *
     * @param \Brick\Money\Money $value
     * @return null|\Brick\Money\Money
     */
    public function apply(Money $value) : ?Money
    {
        return $value->plus($this->tax);
    }
}
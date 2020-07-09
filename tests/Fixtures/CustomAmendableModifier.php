<?php

namespace Tests\Fixtures;

use Money\Money;
use Whitecube\Price\PriceAmendable;

class CustomAmendableModifier implements PriceAmendable
{
    /**
     * The modifier's addition
     *
     * @var \Money\Money
     */
    protected $tax;

    /**
     * Create a new custom instance
     *
     * @param \Money\Money
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
     * @param \Money\Money $value
     * @return null|\Money\Money
     */
    public function apply(Money $value) : ?Money
    {
        return $value->add($this->tax);
    }
}
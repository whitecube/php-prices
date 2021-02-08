<?php

namespace Whitecube\Price;

use Brick\Money\Money;

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
     * Get the modifier attributes that should be saved in the
     * price modification history.
     *
     * @return null|array
     */
    public function attributes() : ?array;

    /**
     * Whether the modifier should be applied before the
     * VAT value has been computed.
     *
     * @return bool
     */
    public function appliesAfterVat() : bool;

    /**
     * Whether this modifier covers a single unit
     * or the whole price regardless of its units.
     *
     * @return bool
     */
    public function appliesPerUnit() : bool;

    /**
     * Apply the modifier on the given Money instance
     *
     * @param \Brick\Money\Money $build
     * @param float $units
     * @param bool $perUnit
     * @param null|\Brick\Money\Money $exclusive
     * @param null|\Whitecube\Price\Vat $vat
     * @return null|\Brick\Money\Money
     */
    public function apply(Money $build, $units, $perUnit, Money $exclusive = null, Vat $vat = null) : ?Money;
}
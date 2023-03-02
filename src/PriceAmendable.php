<?php

namespace Whitecube\Price;

use Brick\Money\AbstractMoney;

interface PriceAmendable
{
    /**
     * Return the modifier type (tax, discount, other, ...)
     *
     * @return string
     */
    public function type() : string;

    /**
     * Define the modifier type (tax, discount, other, ...)
     *
     * @param null|string $type
     * @return $this
     */
    public function setType($type = null);

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
     * Apply the modifier on the given Money instance
     *
     * @param \Brick\Money\AbstractMoney $build
     * @param float $units
     * @param bool $perUnit
     * @param null|\Brick\Money\AbstractMoney $exclusive
     * @param null|\Whitecube\Price\Vat $vat
     * @return null|\Brick\Money\AbstractMoney
     */
    public function apply(AbstractMoney $build, $units, $perUnit, AbstractMoney $exclusive = null, Vat $vat = null) : ?AbstractMoney;
}
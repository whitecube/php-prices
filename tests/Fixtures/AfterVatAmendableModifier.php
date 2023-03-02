<?php

namespace Tests\Fixtures;

use Brick\Money\Money;
use Brick\Math\RoundingMode;
use Whitecube\Price\Vat;
use Whitecube\Price\PriceAmendable;
use Brick\Money\AbstractMoney;

class AfterVatAmendableModifier implements PriceAmendable
{
    /**
     * The "set" modifier type (tax, discount, other, ...)
     *
     * @return null|string
     */
    protected $type;

    /**
     * Return the modifier type (tax, discount, other, ...)
     *
     * @return string
     */
    public function type() : string
    {
        return $this->type;
    }

    /**
     * Define the modifier type (tax, discount, other, ...)
     *
     * @param null|string $type
     * @return $this
     */
    public function setType($type = null)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Return the modifier's identification key
     *
     * @return null|string
     */
    public function key() : ?string
    {
        return 'after-vat';
    }

    /**
     * Get the modifier attributes that should be saved in the
     * price modification history.
     *
     * @return null|array
     */
    public function attributes() : ?array
    {
        return null;
    }

    /**
     * Whether the modifier should be applied before the
     * VAT value has been computed.
     *
     * @return bool
     */
    public function appliesAfterVat() : bool
    {
        return true;
    }

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
    public function apply(AbstractMoney $build, $units, $perUnit, AbstractMoney $exclusive = null, Vat $vat = null) : ?AbstractMoney
    {
        $tax = Money::ofMinor(100, 'EUR');

        return $build->plus($perUnit ? $tax : $tax->multipliedBy($units, RoundingMode::HALF_UP));
    }
}
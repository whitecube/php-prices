<?php

namespace Tests\Fixtures;

use Brick\Money\Money;
use Brick\Math\RoundingMode;
use Whitecube\Price\Vat;
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
     * The "set" modifier type (tax, discount, other, ...)
     *
     * @return null|string
     */
    protected $type;

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
        return 'bar-foo';
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
        return false;
    }

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
    public function apply(Money $build, $units, $perUnit, Money $exclusive = null, Vat $vat = null) : ?Money
    {
        if($perUnit) {
            return $build->plus($this->tax);
        }

        return $build->plus($this->tax->multipliedBy($units, RoundingMode::HALF_UP));
    }
}
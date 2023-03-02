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
     */
    protected ?string $type;

    /**
     * Return the modifier type (tax, discount, other, ...)
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * Define the modifier type (tax, discount, other, ...)
     */
    public function setType(?string $type = null): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Return the modifier's identification key
     */
    public function key(): ?string
    {
        return 'after-vat';
    }

    /**
     * Get the modifier attributes that should be saved in the
     * price modification history.
     */
    public function attributes(): ?array
    {
        return null;
    }

    /**
     * Whether the modifier should be applied before the
     * VAT value has been computed.
     */
    public function appliesAfterVat(): bool
    {
        return true;
    }

    /**
     * Apply the modifier on the given Money instance
     */
    public function apply(AbstractMoney $build, $units, $perUnit, AbstractMoney $exclusive = null, Vat $vat = null) : ?AbstractMoney
    {
        $tax = Money::ofMinor(100, 'EUR');

        return $build->plus($perUnit ? $tax : $tax->multipliedBy($units, RoundingMode::HALF_UP));
    }
}
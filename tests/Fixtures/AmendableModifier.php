<?php

namespace Tests\Fixtures;

use Brick\Math\RoundingMode;
use Whitecube\Price\Vat;
use Whitecube\Price\PriceAmendable;
use Brick\Money\AbstractMoney;

class AmendableModifier implements PriceAmendable
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
        return 'foo-bar';
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
        return false;
    }

    /**
     * Apply the modifier on the given Money instance
     */
    public function apply(AbstractMoney $build, $units, $perUnit, AbstractMoney $exclusive = null, Vat $vat = null) : ?AbstractMoney
    {
        return $build->multipliedBy(1.25, RoundingMode::HALF_UP);
    }
}
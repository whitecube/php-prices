<?php

namespace Tests\Fixtures;

use Brick\Money\Money;
use Brick\Math\RoundingMode;
use Whitecube\Price\Vat;
use Whitecube\Price\PriceAmendable;
use Brick\Money\AbstractMoney;

class CustomAmendableModifier implements PriceAmendable
{
    /**
     * The modifier's addition
     */
    protected Money $tax;
    
    /**
     * The "set" modifier type (tax, discount, other, ...)
     */
    protected ?string $type;

    /**
     * Create a new custom instance
     */
    public function __construct(Money $tax)
    {
        $this->tax = $tax;
    }

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
        return 'bar-foo';
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
        if($perUnit) {
            return $build->plus($this->tax);
        }

        return $build->plus($this->tax->multipliedBy($units, RoundingMode::HALF_UP));
    }
}
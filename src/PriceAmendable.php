<?php

namespace Whitecube\Price;

use Brick\Money\RationalMoney;

interface PriceAmendable
{
    /**
     * Return the modifier type (tax, discount, other, ...)
     */
    public function type(): string;

    /**
     * Define the modifier type (tax, discount, other, ...)
     */
    public function setType(?string $type = null): static;

    /**
     * Return the modifier's identification key
     */
    public function key(): ?string;

    /**
     * Get the modifier attributes that should be saved in the
     * price modification history.
     */
    public function attributes(): ?array;

    /**
     * Whether the modifier should be applied before the
     * VAT value has been computed.
     */
    public function appliesAfterVat(): bool;

    /**
     * Apply the modifier on the given Money instance
     */
    public function apply(RationalMoney $build, float $units, bool $perUnit, RationalMoney $exclusive = null, Vat $vat = null) : ?RationalMoney;
}
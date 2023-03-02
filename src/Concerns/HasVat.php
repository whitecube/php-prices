<?php

namespace Whitecube\Price\Concerns;

use Whitecube\Price\Vat;

trait HasVat
{
    /**
     * Add a VAT value
     *
     * @param mixed $value
     * @return $this
     */
    public function setVat(mixed $value = null): static
    {
        $this->invalidate();

        if(is_string($value)) {
            $value = str_replace(',', '.', trim($value, " \t\n\r\0\x0B%"));
        }

        if(is_null($value) || $value === '') {
            $this->vat = null;

            return $this;
        }

        $this->vat = new Vat($value, $this);

        return $this;
    }

    /**
     * Return the VAT definition object
     */
    public function vat(bool $withoutDefault = false): ?Vat
    {
        return $this->vat
            ?? ($withoutDefault ? null : new Vat(0, $this));
    }
}
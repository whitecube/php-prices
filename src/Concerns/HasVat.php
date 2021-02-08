<?php

namespace Whitecube\Price\Concerns;

use Whitecube\Price\Vat;
use Brick\Money\Money;
use Brick\Math\RoundingMode;

trait HasVat
{
    /**
     * Add a VAT value
     *
     * @param mixed $value
     * @return $this
     */
    public function setVat($value = null)
    {
        $this->invalidate();

        if(is_string($value)) {
            $value = str_replace(',', '.', trim($value, " \t\n\r\0\x0B%"));
        }

        if(is_null($value) || $value === '') {
            $this->vat = null;

            return $this;
        }

        $this->vat = new Vat($value, $this->currency());

        return $this;
    }

    /**
     * Return the VAT definition object
     *
     * @return null|\Whitecube\Price\Vat
     */
    public function vat()
    {
        $this->build();

        return $this->vat;
    }
}
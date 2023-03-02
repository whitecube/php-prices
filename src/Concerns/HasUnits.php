<?php

namespace Whitecube\Price\Concerns;

trait HasUnits
{
    /**
     * Define the total units count
     */
    public function setUnits(mixed $value): static
    {
        $this->invalidate();
        
        $this->units = floatval(str_replace(',', '.', $value));

        return $this;
    }

    /**
     * Return the total units count
     */
    public function units(): float
    {
        return $this->units;
    }
}
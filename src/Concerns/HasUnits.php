<?php

namespace Whitecube\Price\Concerns;

trait HasUnits
{
    /**
     * Define the total units count
     *
     * @param mixed $value
     * @return $this
     */
    public function setUnits($value)
    {
        $this->units = floatval(str_replace(',', '.', $value));

        return $this;
    }

    /**
     * Return the total units count
     *
     * @return float
     */
    public function units()
    {
        return $this->units;
    }
}
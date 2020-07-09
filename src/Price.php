<?php

namespace Whitecube\Price;

use Money\Money;
use Money\Currency;

class Price
{
    /**
     * The root price
     *
     * @var \Money\Money
     */
    protected $base;

    /**
     * The base exclusive price (after modification)
     *
     * @var null|\Money\Money
     */
    protected $excl;

    /**
     * The VAT's percentage of the base price
     *
     * @var null|float
     */
    protected $vat;

    /**
     * The amount of times the base price is multiplied
     *
     * @var float
     */
    protected $units;

    /**
     * Create a new Price object
     *
     * @param \Money\Money $base
     * @param int $units
     * @return void
     */
    public function __construct(Money $base, $units = 1)
    {
        $this->base = $base;
        $this->setUnits($units);
    }

    /**
     * Return the price's base value
     *
     * @param bool $perUnit
     * @return \Money\Money
     */
    public function base($perUnit = true)
    {
        return $this->base->multiply($perUnit ? 1 : $this->units);
    }

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

    /**
     * Add a VAT value
     *
     * @param mixed $value
     * @return $this
     */
    public function setVat($value = null)
    {
        if(is_null($value)) {
            $this->vat = null;
        } elseif (is_a($value, Money::class)) {
            $this->vat = (100 / $this->base->ratioOf($value));
        } else {
            $this->vat = floatval(str_replace(',', '.', $value));
        }

        return $this;
    }

    /**
     * Return the VAT Money value
     *
     * @param bool $perUnit
     * @return null|\Money\Money
     */
    public function vat($perUnit = false)
    {
        if(is_null($this->vat)) {
            return null;
        }

        return $this->base->multiply(
            ($perUnit ? 1 : $this->units) * ($this->vat / 100)
        );
    }

    /**
     * Return the VAT Money value
     *
     * @return null|float
     */
    public function vatPercentage()
    {
        return $this->vat;
    }

    /**
     * Return the EXCL. Money value
     *
     * @param bool $perUnit
     * @return \Money\Money
     */
    public function exclusive($perUnit = false)
    {
        return ($this->excl ?? $this->base)
            ->multiply($perUnit ? 1 : $this->units);
    }

    /**
     * Return the INCL. Money value
     *
     * @param bool $perUnit
     * @return \Money\Money
     */
    public function inclusive($perUnit = false)
    {
        if(is_null($this->vat)) {
            return $this->exclusive($perUnit);
        }

        return $this->exclusive($perUnit)
            ->add($this->vat($perUnit));
    }

    /**
     * Forward operations on the price's base value
     *
     * @param string $method
     * @param array  $arguments
     * @return $this|mixed
     */
    public function __call($method, $arguments)
    {
        $result = call_user_func_array([$this->base, $method], $arguments);

        if(!is_a($result, Money::class)) {
            return $result;
        }

        $this->base = $result;

        return $this;
    }

    /**
     * Convenience Money method for creating a Price object
     *
     * @param string $method
     * @param array  $arguments
     * @return static
     */
    public static function __callStatic($method, $arguments)
    {
        $base = new Money($arguments[0], new Currency($method));

        return new static($base, $arguments[1] ?? 1);
    }
}
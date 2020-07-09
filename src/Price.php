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
     * @return \Money\Money
     */
    public function base()
    {
        return $this->base;
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
     * @return null|\Money\Money
     */
    public function vat()
    {
        if(is_null($this->vat)) {
            return null;
        }

        return $this->base->multiply($this->vat / 100);
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
     * @return \Money\Money
     */
    public function exclusive()
    {
        return $this->excl ?? $this->base;
    }

    /**
     * Return the INCL. Money value
     *
     * @return \Money\Money
     */
    public function inclusive()
    {
        if(is_null($this->vat)) {
            return $this->exclusive();
        }

        return $this->exclusive()->add($this->vat());
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
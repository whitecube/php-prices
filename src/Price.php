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
     * The VAT's Money amount
     *
     * @var null|\Money\Money
     */
    protected $vatAmount;

    /**
     * The VAT's percentage of the base price
     *
     * @var null|float
     */
    protected $vatPercentage;

    /**
     * Create a new Price object
     *
     * @param \Money\Money $base
     * @return void
     */
    public function __construct(Money $base)
    {
        $this->base = $base;
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
     * Add a VAT value
     *
     * @param mixed $value
     * @return this
     */
    public function setVat($value = null)
    {
        if(is_null($value)) {
            $this->vatAmount = null;
            $this->vatPercentage = null;
            return $this;
        }

        if(is_a($value, Money::class)) {
            $this->vatAmount = $value;
            $this->vatPercentage = (100 / $this->base->ratioOf($value));
            return $this;
        }

        $this->vatPercentage = floatval(str_replace(',', '.', $value));
        $this->vatAmount = $this->base->multiply($this->vatPercentage / 100);
        return $this;
    }

    /**
     * Return the VAT Money value
     *
     * @return null|\Money\Money
     */
    public function vat()
    {
        return $this->vatAmount;
    }

    /**
     * Return the VAT Money value
     *
     * @return null|float
     */
    public function vatPercentage()
    {
        return $this->vatPercentage;
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
        if(is_null($this->vatAmount)) {
            return $this->exclusive();
        }

        return $this->exclusive()->add($this->vatAmount);
    }

    /**
     * Forward operations on the price's base value
     *
     * @param string $method
     * @param array  $arguments
     * @return this|mixed
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

        return new static($base);
    }
}
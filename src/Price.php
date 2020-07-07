<?php

namespace Whitecube\Price;

use Money\Money;
use Money\Currency;

class Price
{
    /**
     * The base (exclusive) price
     *
     * @var \Money\Money
     */
    protected $base;

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
     * @return void
     */
    public function base()
    {
        return $this->base;
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
     * @return Money
     */
    public static function __callStatic($method, $arguments)
    {
        $base = new Money($arguments[0], new Currency($method));

        return new static($base);
    }
}
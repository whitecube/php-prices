<?php

namespace Whitecube\Price\Concerns;

use Whitecube\Price\Price;
use Brick\Money\Money;

trait OperatesOnBase
{
    /**
     * Forward operations on the price's base value
     *
     * @param string $method
     * @param array  $arguments
     * @return $this|mixed
     */
    public function __call($method, $arguments)
    {
        $arguments = array_map(function($value) {
            return is_a($value, Price::class) ? $value->base() : $value;
        }, $arguments);

        $result = call_user_func_array([$this->base, $method], $arguments);

        if(! is_a($result, Money::class)) {
            return $result;
        }

        $this->base = $result;

        return $this;
    }

    /**
     * Check if given value equals the price's base value
     *
     * @param mixed $value
     * @return bool
     */
    public function equals($value)
    {
        if(is_a($value, Price::class)) {
            $value = $value->base();
        }

        if(! is_a($value, Money::class)) {
            $value = Money::of($value, $this->base->getCurrency());
        }

        $valueCurrencyCode = $value->getCurrency()->getCurrencyCode();
        $baseCurrencyCode = $this->base->getCurrency()->getCurrencyCode();

        if($valueCurrencyCode === $baseCurrencyCode) {
            return $this->base->getAmount()->compareTo($value->getAmount()) === 0;
        }

        throw new \Exception('Could not compare values in different currencies.');
    }
}
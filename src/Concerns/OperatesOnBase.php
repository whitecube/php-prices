<?php

namespace Whitecube\Price\Concerns;

use Whitecube\Price\Price;
use Brick\Money\AbstractMoney;
use Brick\Money\Exception\MoneyMismatchException;
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

        if(! is_a($result, AbstractMoney::class)) {
            return $result;
        }

        $this->base = $result;
        
        $this->invalidate();

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
        return $this->compareTo($value) === 0;
    }

    /**
     * Compare a given value to the total inclusive value of this instance
     *
     * @param mixed $value
     * @return int
     */
    public function compareTo($value)
    {
        return $this->compareMonies(
            $this->inclusive(),
            $this->valueToMoney($value)
        );
    }

    /**
     * Compare a given value to the unitless base value of this instance
     *
     * @param mixed $value
     * @return int
     */
    public function compareBaseTo($value)
    {
        return $this->compareMonies(
            $this->base(),
            $this->valueToMoney($value, 'base')
        );
    }

    /**
     * Compare the given "current" value to another value
     *
     * @param \Brick\Money\AbstractMoney $price
     * @param \Brick\Money\AbstractMoney $that
     * @return int
     * @throws \Brick\Money\Exception\MoneyMismatchException
     */
    protected function compareMonies(AbstractMoney $price, AbstractMoney $that)
    {
        $priceCurrency = $price->getCurrency();
        $thatCurrency = $that->getCurrency();

        if($priceCurrency->getCurrencyCode() === $thatCurrency->getCurrencyCode()) {
            return $price->getAmount()->compareTo($that->getAmount());
        }

        throw MoneyMismatchException::currencyMismatch($priceCurrency, $thatCurrency);
    }

    /**
     * Transform a given value into a Money instance
     *
     * @param mixed $value
     * @param string $method
     * @return \Brick\Money\AbstractMoney
     */
    protected function valueToMoney($value, $method = 'inclusive')
    {
        if(is_a($value, Price::class)) {
            $value = $value->$method();
        }

        if(is_a($value, AbstractMoney::class)) {
            return $value;
        }

        return Money::ofMinor($value, $this->currency());
    }
}
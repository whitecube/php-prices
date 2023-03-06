<?php

namespace Whitecube\Price\Concerns;

use Whitecube\Price\Price;
use Brick\Money\AbstractMoney;
use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Money;
use Brick\Math\BigNumber;

trait OperatesOnBase
{
    /**
     * Forward operations on the price's base value
     */
    public function __call(string $method, array $arguments): mixed
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
     */
    public function equals(BigNumber|int|float|string|AbstractMoney|Price $value): bool
    {
        return $this->compareTo($value) === 0;
    }

    /**
     * Compare a given value to the total inclusive value of this instance
     */
    public function compareTo(BigNumber|int|float|string|AbstractMoney|Price $value): int
    {
        return $this->compareMonies(
            $this->inclusive(),
            $this->valueToMoney($value)
        );
    }

    /**
     * Compare a given value to the unitless base value of this instance
     */
    public function compareBaseTo(BigNumber|int|float|string|AbstractMoney|Price $value): int
    {
        return $this->compareMonies(
            $this->base(),
            $this->valueToMoney($value, 'base')
        );
    }

    /**
     * Compare the given "current" value to another value
     * @throws \Brick\Money\Exception\MoneyMismatchException
     */
    protected function compareMonies(AbstractMoney $price, AbstractMoney $that): int
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
     */
    protected function valueToMoney(BigNumber|int|float|string|AbstractMoney|Price $value, string $method = 'inclusive'): AbstractMoney
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
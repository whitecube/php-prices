<?php

namespace Whitecube\Price\Concerns;

use Whitecube\Price\Price;
use Brick\Money\AbstractMoney;
use Brick\Money\RationalMoney;
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

        if(! is_a($result, RationalMoney::class)) {
            return $result;
        }

        $this->base = $result;
        
        $this->invalidate();

        return $this;
    }

    /**
     * Check if the provided amount equals this price's total inclusive amount
     */
    public function equals(BigNumber|int|float|string|AbstractMoney|Price $that): bool
    {
        return $this->compareTo($that) === 0;
    }

    /**
     * Compare the provided amount to this price's total inclusive amount
     */
    public function compareTo(BigNumber|int|float|string|AbstractMoney|Price $that): int
    {
        return $this->compareMonies(
            $this->inclusive(),
            $this->valueToRationalMoney($that, 'inclusive')
        );
    }

    /**
     * Compare a provided amount to this price's unitless base amount
     */
    public function compareBaseTo(BigNumber|int|float|string|AbstractMoney|Price $that): int
    {
        return $this->compareMonies(
            $this->base(),
            $this->valueToRationalMoney($that, 'base')
        );
    }

    /**
     * Compare the provided "current" amount to another amount
     * @throws \Brick\Money\Exception\MoneyMismatchException
     */
    protected function compareMonies(RationalMoney $current, RationalMoney $that): int
    {
        $currentCurrency = $this->currency();
        $thatCurrency = $that->getCurrency();

        if($currentCurrency->getCurrencyCode() === $thatCurrency->getCurrencyCode()) {
            return $current->getAmount()->compareTo($that->getAmount());
        }

        throw MoneyMismatchException::currencyMismatch($currentCurrency, $thatCurrency);
    }
}
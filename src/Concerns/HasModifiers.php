<?php

namespace Whitecube\Price\Concerns;

use Whitecube\Price\Price;
use Whitecube\Price\Modifier;
use Whitecube\Price\PriceAmendable;
use Brick\Money\AbstractMoney;
use Brick\Money\Money;

trait HasModifiers
{
    /**
     * Add a tax modifier
     */
    public function addTax(string|callable|AbstractMoney|Price|PriceAmendable $modifier, ...$arguments): static
    {
        return $this->addModifier(Modifier::TYPE_TAX, $modifier, ...$arguments);
    }

    /**
     * Add a discount modifier
     */
    public function addDiscount(string|callable|AbstractMoney|Price|PriceAmendable $modifier, ...$arguments): static
    {
        return $this->addModifier(Modifier::TYPE_DISCOUNT, $modifier, ...$arguments);
    }

    /**
     * Add a price modifier
     */
    public function addModifier(string $type, string|callable|AbstractMoney|Price|PriceAmendable $modifier, ...$arguments): static
    {
        $this->modifiers[] = $this->makeModifier($type, $modifier, $arguments);

        $this->invalidate();

        return $this;
    }

    /**
     * Create a usable modifier instance
     * @throws \InvalidArgumentException
     */
    protected function makeModifier(string $type, string|callable|AbstractMoney|Price|PriceAmendable $modifier, array $arguments = []): PriceAmendable
    {
        if(is_string($modifier) && class_exists($modifier)) {
            $modifier = new $modifier(...$arguments);
        }

        if(is_a($modifier, PriceAmendable::class)) {
            return $modifier->setType($type);
        }

        if($modifier === '') {
            throw new \InvalidArgumentException('Price modifier cannot be empty.');
        }

        $instance = (new Modifier)->setType($type);

        if (is_callable($modifier)) {
            $modifier($instance);

            return $instance;
        }

        if(is_a($modifier, Price::class)) {
            $modifier = $modifier->inclusive();
        }

        return $instance->add($modifier, Price::getRounding('exclusive'));
    }

    /**
     * Get the defined modifiers from before or after the
     * VAT value should have been applied
     */
    public function getVatModifiers(bool $postVat): array
    {
        return array_filter($this->modifiers, function($modifier) use ($postVat) {
            return $modifier->appliesAfterVat() === $postVat;
        });
    }

    /**
     * Return the current modifications history
     */
    public function modifications(bool $perUnit = false, ?string $type = null): array
    {
        $result = $this->build()->inclusive($perUnit ? true : false);

        if(is_null($type)) {
            return array_values($result['modifications']);
        }

        return array_values(array_filter($result['modifications'], function($modification) use ($type) {
            return $modification['type'] === $type;
        }));
    }

    /**
     * Return the modification total for all discounts
     */
    public function discounts(bool $perUnit = false): Money
    {
        return $this->modifiers($perUnit, Modifier::TYPE_DISCOUNT);
    }

    /**
     * Return the modification total for all taxes
     */
    public function taxes(bool $perUnit = false): Money
    {
        return $this->modifiers($perUnit, Modifier::TYPE_TAX);
    }

    /**
     * Return the modification total for a given type
     */
    public function modifiers(bool $perUnit = false, ?string $type = null): Money
    {
        $amount = Money::zero($this->currency());

        foreach ($this->modifications($perUnit, $type) as $modification) {
            $amount = $amount->plus($modification['amount']);
        }

        return $amount;
    }
}
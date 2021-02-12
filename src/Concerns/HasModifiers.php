<?php

namespace Whitecube\Price\Concerns;

use Whitecube\Price\Price;
use Whitecube\Price\Modifier;
use Whitecube\Price\PriceAmendable;
use Brick\Money\Money;

trait HasModifiers
{
    /**
     * Add a tax modifier
     *
     * @param mixed $modifier
     * @param array $arguments
     * @return $this
     */
    public function addTax($modifier, ...$arguments)
    {
        return $this->addModifier(Modifier::TYPE_TAX, $modifier, ...$arguments);
    }

    /**
     * Add a discount modifier
     *
     * @param mixed $modifier
     * @param array $arguments
     * @return $this
     */
    public function addDiscount($modifier, ...$arguments)
    {
        return $this->addModifier(Modifier::TYPE_DISCOUNT, $modifier, ...$arguments);
    }

    /**
     * Add a price modifier
     *
     * @param string $type
     * @param mixed $modifier
     * @param array $arguments
     * @return $this
     */
    public function addModifier($type, $modifier, ...$arguments)
    {
        $this->modifiers[] = $this->makeModifier($type, $modifier, $arguments);

        $this->invalidate();

        return $this;
    }

    /**
     * Create a usable modifier instance
     *
     * @param string $type
     * @param mixed $modifier
     * @param array $arguments
     * @return \Whitecube\Price\PriceAmendable
     * @throws \InvalidArgumentException
     */
    protected function makeModifier($type, $modifier, $arguments = [])
    {
        if(is_string($modifier) && class_exists($modifier)) {
            $modifier = new $modifier(...$arguments);
        }

        if(is_a($modifier, PriceAmendable::class)) {
            return $modifier->setType($type);
        }

        if(is_null($modifier) || $modifier === '') {
            throw new \InvalidArgumentException('Price modifier cannot be null or empty.');
        }

        $instance = (new Modifier)->setType($type);

        if (is_callable($modifier)) {
            $modifier($instance);

            return $instance;
        }

        if(is_a($modifier, Price::class)) {
            $modifier = $modifier->inclusive();
        }

        if(is_object($modifier) && ! is_a($modifier, Money::class)) {
            throw new \InvalidArgumentException('Price modifier instance should implement "' . PriceAmendable::class . '".');
        } elseif (! is_object($modifier) && ! is_numeric($modifier)) {
            throw new \InvalidArgumentException('Price modifier cannot be of type ' . gettype($modifier) . '.');
        }

        return $instance->add($modifier, Price::getRounding('exclusive'));
    }

    /**
     * Get the defined modifiers from before or after the
     * VAT value should have been applied
     *
     * @param bool $postVat
     * @return array
     */
    public function getVatModifiers(bool $postVat)
    {
        return array_filter($this->modifiers, function($modifier) use ($postVat) {
            return $modifier->appliesAfterVat() === $postVat;
        });
    }

    /**
     * Return the current modifications history
     *
     * @param bool $perUnit
     * @param null|string $type
     * @return array
     */
    public function modifications($perUnit = false, $type = null)
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
     *
     * @param bool $perUnit
     * @return \Brick\Money\Money
     */
    public function discounts($perUnit = false)
    {
        return $this->modifiers($perUnit, Modifier::TYPE_DISCOUNT);
    }

    /**
     * Return the modification total for all taxes
     *
     * @param bool $perUnit
     * @return \Brick\Money\Money
     */
    public function taxes($perUnit = false)
    {
        return $this->modifiers($perUnit, Modifier::TYPE_TAX);
    }

    /**
     * Return the modification total for a given type
     *
     * @param bool $perUnit
     * @param null|string $type
     * @return \Brick\Money\Money
     */
    public function modifiers($perUnit = false, $type = null)
    {
        $amount = Money::zero($this->currency());

        foreach ($this->modifications($perUnit, $type) as $modification) {
            $amount = $amount->plus($modification['amount']);
        }

        return $amount;
    }
}
<?php

namespace Whitecube\Price\Concerns;

use Whitecube\Price\Price;
use Whitecube\Price\Modifier;
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
    public function addDiscount($modifier)
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
        }

        return $instance->add($modifier, Price::getRounding('exclusive'));
    }
}
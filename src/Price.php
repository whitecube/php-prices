<?php

namespace Whitecube\Price;

use Money\Money;
use Money\Currency;

class Price implements \JsonSerializable
{
    use ParsesPrices {
        ParsesPrices::__callStatic as callPriceParser;
    }

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
     * The price modifiers to apply
     *
     * @var array
     */
    protected $modifiers = [];

    /**
     * The modified results
     *
     * @var array
     */
    protected $modifications = [];

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
     * @param bool $perUnit
     * @return \Money\Money
     */
    public function base($perUnit = true)
    {
        return $this->base->multiply($perUnit ? 1 : $this->units);
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
     * @param bool $perUnit
     * @return null|\Money\Money
     */
    public function vat($perUnit = false)
    {
        if(is_null($this->vat)) {
            return null;
        }

        $base = $this->applyModifiers(
            $this->base, $this->getModifiers(true), false
        );

        return $base->multiply(
            ($perUnit ? 1 : $this->units) * ($this->vat / 100)
        );
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
     * @param bool $perUnit
     * @return \Money\Money
     */
    public function exclusive($perUnit = false)
    {
        return $this->getModifiedBase()
            ->multiply($perUnit ? 1 : $this->units);
    }

    /**
     * Return the INCL. Money value
     *
     * @param bool $perUnit
     * @return \Money\Money
     */
    public function inclusive($perUnit = false)
    {
        if(is_null($this->vat)) {
            return $this->exclusive($perUnit);
        }

        return $this->exclusive($perUnit)
            ->add($this->vat($perUnit));
    }

    /**
     * Add a tax modifier
     *
     * @param mixed $modifier
     * @param null|string $key
     * @param null|bool $pre
     * @return $this
     */
    public function addTax($modifier, $key = null, $pre = null)
    {
        return $this->addModifier($modifier, $key, Modifier::TYPE_TAX, $pre);
    }

    /**
     * Add a discount modifier
     *
     * @param mixed $modifier
     * @param null|string $key
     * @param null|bool $pre
     * @return $this
     */
    public function addDiscount($modifier, $key = null, $pre = null)
    {
        return $this->addModifier($modifier, $key, Modifier::TYPE_DISCOUNT, $pre);
    }

    /**
     * Add a price modifier
     *
     * @param array $arguments
     * @return $this
     */
    public function addModifier(...$arguments)
    {
        $this->modifiers[] = $this->makeModifier($arguments);

        $this->excl = null;
        $this->modifications = [];

        return $this;
    }

    /**
     * Return the current modifications history
     *
     * @param null|string $type
     * @return array
     */
    public function modifications($type = null)
    {
        if(is_null($this->excl)) {
            $this->getModifiedBase();
        }

        $modifications = is_null($type) 
            ? $this->modifications
            : array_filter($this->modifications, function($item) use ($type) {
                return $item['type'] === $type;
            });

        return array_values($modifications);
    }

    /**
     * Create a usable modifier instance
     *
     * @param array $arguments
     * @return \Whitecube\Price\PriceAmendable
     * @throws \InvalidArgumentException
     */
    protected function makeModifier(array $arguments)
    {
        $modifier = array_shift($arguments);

        if(is_null($modifier)) {
            throw new \InvalidArgumentException('Cannot create modifier from NULL value.');
        }

        if(is_numeric($modifier)) {
            $modifier = new Money($modifier, $this->base->getCurrency());
        }

        if(is_a($modifier, Money::class)) {
            $modifier = function(Money $value) use ($modifier) {
                return $value->add($modifier);
            };
        }

        if (is_callable($modifier)) {
            [$key, $type, $pre] = $this->extractModifierArguments($arguments);
            $modifier = new Modifier($modifier, $key, $type, $pre);
        } elseif (is_string($modifier) && class_exists($modifier)) {
            $modifier = new $modifier(...$arguments);
        }

        if(!is_a($modifier, PriceAmendable::class)) {
            throw new \InvalidArgumentException('Price modifier instance should implement "' . PriceAmendable::class . '".');
        }

        return $modifier;
    }

    /**
     * Finds the named arguments from a loose modifier call
     *
     * @param array $arguments
     * @return array
     */
    protected function extractModifierArguments(array $arguments)
    {
        switch (count($arguments)) {
            case 0:
                return [null, null, false];
            case 1:
                return [$arguments[0] ?: null, null, false];
            case 2:
                return [$arguments[0] ?: null, $arguments[1] ?: null, false];
        }

        return [
            ($arguments[0] ?? null) ?: null,
            ($arguments[1] ?? null) ?: null,
            boolval($arguments[2] ?? null),
        ];
    }

    /**
     * Get the price's modified exclusive base price
     *
     * @return \Money\Money
     */
    protected function getModifiedBase()
    {
        if(!is_null($this->excl)) {
            return $this->excl;
        }

        $this->modifications = [];

        $withoutVat = $this->applyModifiers(
            $this->base, $this->getModifiers(true), true
        );

        return $this->excl = $this->applyModifiers(
            $withoutVat, $this->getModifiers(false), true
        );
    }

    /**
     * Apply the given modifiers array on the given base price
     *
     * @param \Money\Money $base
     * @param array $modifiers
     * @param bool $log
     * @return \Money\Money
     */
    protected function applyModifiers(Money $base, array $modifiers, $log = true)
    {
        return array_reduce($modifiers, function($base, $modifier) use ($log) {
            $result = $modifier->apply($base);

            if(!$result) return $base;

            if($log) $this->pushModifierResult($result->subtract($base), $modifier);

            return $result;
        }, $base);
    }

    /**
     * Add a modifier's result to the modified history array
     *
     * @param \Money\Money $amount
     * @param \Whitecube\Price\PriceAmendable $modifier
     * @return void
     */
    protected function pushModifierResult(Money $amount, PriceAmendable $modifier)
    {
        $this->modifications[] = [
            'type' => $modifier->type(),
            'key' => $modifier->key(),
            'amount' => $amount
        ];
    }

    /**
     * Get the defined modifiers from before or after the
     * VAT value should have been applied
     *
     * @param bool $before
     * @return array
     */
    protected function getModifiers(bool $before)
    {
        return array_filter($this->modifiers, function($modifier) use ($before) {
            return $modifier->isBeforeVat() === $before;
        });
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
        try {
            $price = static::callPriceParser($method, $arguments);
        } catch (\BadMethodCallException $e) {
            $base = new Money($arguments[0], new Currency($method));

            $price = new static($base, $arguments[1] ?? 1);
        }

        return $price;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function jsonSerialize()
    {
        $base = $this->base->jsonSerialize();
        $excl = $this->exclusive()->jsonSerialize();
        $incl = $this->inclusive()->jsonSerialize();

        return [
            'base' => $base['amount'],
            'currency' => $base['currency'],
            'units' => $this->units,
            'vat' => $this->vat,
            'total' => [
                'exclusive' => $excl['amount'],
                'inclusive' => $incl['amount'],
            ],
        ];
    }

    /**
     * Hydrate a price object from a json string/array
     *
     * @param mixed $value
     * @return static
     * @throws \InvalidArgumentException
     */
    public static function json($value)
    {
        if(is_string($value)) {
            $value = json_decode($value, true);
        }

        if(!is_array($value)) {
            throw new \InvalidArgumentException('Cannot create Price from invalid argument (expects JSON string or Array)');
        }

        $base = new Money($value['base'], new Currency($value['currency']));
        
        return (new static($base, $value['units']))
            ->setVat($value['vat']);
    }
}
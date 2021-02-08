<?php

namespace Whitecube\Price;

use Brick\Money\Money;

class Modifier implements PriceAmendable
{
    /**
     * The modifier types
     *
     * @var string
     */
    const TYPE_TAX = 'tax';
    const TYPE_DISCOUNT = 'discount';
    const TYPE_UNDEFINED = 'other';

    /**
     * The effective modifier type
     *
     * @var null|string
     */
    protected $type;

    /**
     * The modifier's identifier
     *
     * @var null|string
     */
    protected $key;

    /**
     * The modifier's currency
     *
     * @var null|string
     */
    protected $currency;

    /**
     * Whether this modifier should be executed
     * before or after the VAT value has been computed.
     *
     * @var bool
     */
    protected $postVat = false;

    /**
     * Whether this modifier covers a single unit
     * or the whole price regardless of its units.
     *
     * @var bool
     */
    protected $perUnit = true;

    /**
     * The modifications that should be applied to the price
     *
     * @var array
     */
    protected $stack = [];

    /**
     * Create a new modifier instance
     *
     * @param mixed $callback
     * @param null|string $type
     * @param bool $pre
     * @return static
     */
    static public function of($configuration)
    {
        return (new static())
            ->setType($configuration['type'] ?? null)
            ->setKey($configuration['key'] ?? null)
            ->setPostVat($configuration['postVat'] ?? false)
            ->setPerUnit($configuration['perUnit'] ?? true)
            ->setCurrency($configuration['currency'] ?? null);
    }

    /**
     * Define the modifier type (tax, discount, other, ...)
     *
     * @param null|string $type
     * @return $this
     */
    public function setType($type = null)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Return the modifier type (tax, discount, other, ...)
     *
     * @return string
     */
    public function type() : string
    {
        return $this->type ?: static::TYPE_UNDEFINED;
    }

    /**
     * Define the modifier's identification key
     *
     * @param null|string $key
     * @return $this
     */
    public function setKey($key = null)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Return the modifier's identification key
     *
     * @return null|string
     */
    public function key() : ?string
    {
        return $this->key;
    }

    /**
     * Define the modifier's identification key
     *
     * @param null|string|\Brick\Money\Currency $currency
     * @return $this
     */
    public function setCurrency($currency = null)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get the modifier attributes that should be saved in the
     * price modification history.
     *
     * @return null|array
     */
    public function attributes() : ?array
    {
        return null;
    }

    /**
     * Whether the modifier should be applied before the
     * VAT value has been computed.
     *
     * @param bool $postVat
     * @return $this
     */
    public function setPostVat($postVat = true)
    {
        $this->postVat = $postVat;

        return $this;
    }

    /**
     * Whether the modifier should be applied before the
     * VAT value has been computed.
     *
     * @return bool
     */
    public function appliesAfterVat() : bool
    {
        return $this->postVat ? true : false;
    }

    /**
     * Whether this modifier covers a single unit
     * or the whole price regardless of its units.
     *
     * @param bool $perUnit
     * @return $this
     */
    public function setPerUnit($perUnit = true)
    {
        $this->perUnit = $perUnit;

        return $this;
    }

    /**
     * Whether this modifier covers a single unit
     * or the whole price regardless of its units.
     *
     * @return bool
     */
    public function appliesPerUnit() : bool
    {
        return $this->perUnit ? true : false;
    }

    /**
     * Add an addition modification to the stack
     *
     * @param array $arguments
     * @return $this
     */
    public function add(...$arguments)
    {
        $this->stack[] = [
            'method' => 'plus',
            'arguments' => $arguments
        ];

        return $this;
    }

    /**
     * Add a substraction modification to the stack
     *
     * @param array $arguments
     * @return $this
     */
    public function subtract(...$arguments)
    {
        $this->stack[] = [
            'method' => 'minus',
            'arguments' => $arguments
        ];

        return $this;
    }

    /**
     * Add a multiplication modification to the stack
     *
     * @param array $arguments
     * @return $this
     */
    public function multiply(...$arguments)
    {
        $this->stack[] = [
            'method' => 'multipliedBy',
            'arguments' => $arguments
        ];

        return $this;
    }

    /**
     * Add a division modification to the stack
     *
     * @param array $arguments
     * @return $this
     */
    public function divide(...$arguments)
    {
        $this->stack[] = [
            'method' => 'dividedBy',
            'arguments' => $arguments
        ];

        return $this;
    }

    /**
     * Add a absolute modification to the stack
     *
     * @param mixed $that
     * @param null|int $rounding
     * @return $this
     */
    public function abs()
    {
        $this->stack[] = [
            'method' => 'abs',
        ];

        return $this;
    }

    /**
     * Apply the modifier on the given Money instance
     *
     * @param \Brick\Money\Money $build
     * @param float $units
     * @param bool $perUnit
     * @param null|\Brick\Money\Money $exclusive
     * @param null|\Whitecube\Price\Vat $vat
     * @return null|\Brick\Money\Money
     */
    public function apply(Money $build, $units, $perUnit, Money $exclusive = null, Vat $vat = null) : ?Money
    {
        if(! $this->stack) {
            return null;
        }

        return array_reduce($this->stack, function($build, $action) use ($units, $perUnit) {
            if(! in_array($action['method'], ['plus', 'minus'])) {
                return $this->applyStackAction($action, $build);
            }

            if($this->appliesPerUnit() && (! $perUnit) && $units > 1) {
                $argument = is_a($action['arguments'][0] ?? null, Money::class)
                    ? $action['arguments'][0]
                    : Money::ofMinor($action['arguments'][0] ?? 0, $build->getCurrency());

                $action['arguments'][0] = $argument->multipliedBy($units, Price::getRounding('exclusive'));
            }

            return $this->applyStackAction($action, $build);
        }, $build);
    }

    /**
     * Apply given stack action on the price being build
     *
     * @param array $action
     * @param \Brick\Money\Money $build
     * @return \Brick\Money\Money
     */
    protected function applyStackAction($action, Money $build)
    {
        return call_user_func_array([$build, $action['method']], $action['arguments'] ?? []);
    }
}
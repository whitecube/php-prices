<?php

namespace Whitecube\Price;

use Brick\Money\Money;
use Brick\Money\RationalMoney;

class Modifier implements PriceAmendable
{
    /**
     * The default modifier types
     */
    const TYPE_TAX = 'tax';
    const TYPE_DISCOUNT = 'discount';
    const TYPE_UNDEFINED = 'undefined';

    /**
     * The effective modifier type
     */
    protected ?string $type = null;

    /**
     * The modifier's identifier
     */
    protected ?string $key = null;

    /**
     * The extra attributes that should be passed along
     */
    protected array $attributes = [];

    /**
     * Whether this modifier should be executed
     * before or after the VAT value has been computed.
     */
    protected bool $postVat = false;

    /**
     * Whether this modifier covers a single unit
     * or the whole price regardless of its units.
     */
    protected bool $perUnit = true;

    /**
     * The modifications that should be applied to the price
     */
    protected array $stack = [];

    /**
     * Create a new modifier instance
     */
    static public function of(array $configuration): static
    {
        return (new static())
            ->setType($configuration['type'] ?? null)
            ->setKey($configuration['key'] ?? null)
            ->setPostVat($configuration['postVat'] ?? false)
            ->setPerUnit($configuration['perUnit'] ?? true);
    }

    /**
     * Define the modifier type (tax, discount, other, ...)
     */
    public function setType(?string $type = null): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Return the modifier type (tax, discount, other, ...)
     */
    public function type(): string
    {
        return $this->type ?: static::TYPE_UNDEFINED;
    }

    /**
     * Define the modifier's identification key
     */
    public function setKey(?string $key = null): static
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Return the modifier's identification key
     */
    public function key(): ?string
    {
        return $this->key;
    }

    /**
     * Define the modifier's extra attributes
     */
    public function setAttributes(array $attributes = []): static
    {
        $this->attributes = $attributes;

        return $this;
    }
    /**
     * Get the modifier attributes that should be saved in the
     * price modification history.
     */
    public function attributes(): ?array
    {
        return $this->attributes ?: null;
    }

    /**
     * Whether the modifier should be applied before the
     * VAT value has been computed.
     */
    public function setPostVat(bool $postVat = true): static
    {
        $this->postVat = $postVat;

        return $this;
    }

    /**
     * Whether the modifier should be applied before the
     * VAT value has been computed.
     */
    public function appliesAfterVat(): bool
    {
        return $this->postVat ? true : false;
    }

    /**
     * Whether this modifier covers a single unit
     * or the whole price regardless of its units.
     */
    public function setPerUnit(bool $perUnit = true): static
    {
        $this->perUnit = $perUnit;

        return $this;
    }

    /**
     * Whether this modifier covers a single unit
     * or the whole price regardless of its units.
     */
    public function appliesPerUnit(): bool
    {
        return $this->perUnit ? true : false;
    }

    /**
     * Add an addition modification to the stack
     */
    public function add($argument): static
    {
        $this->stack[] = [
            'method' => 'plus',
            'argument' => $argument
        ];

        return $this;
    }

    /**
     * Add a substraction modification to the stack
     */
    public function subtract($argument): static
    {
        $this->stack[] = [
            'method' => 'minus',
            'argument' => $argument
        ];

        return $this;
    }

    /**
     * Add a multiplication modification to the stack
     */
    public function multiply($argument): static
    {
        $this->stack[] = [
            'method' => 'multipliedBy',
            'argument' => $argument
        ];

        return $this;
    }

    /**
     * Add a division modification to the stack
     */
    public function divide($argument): static
    {
        $this->stack[] = [
            'method' => 'dividedBy',
            'argument' => $argument
        ];

        return $this;
    }

    /**
     * Add a absolute modification to the stack
     */
    public function abs(): static
    {
        $this->stack[] = [
            'method' => 'abs',
        ];

        return $this;
    }

    /**
     * Apply the modifier on the given Money instance
     */
    public function apply(RationalMoney $build, $units, $perUnit, RationalMoney $exclusive = null, Vat $vat = null) : ?RationalMoney
    {
        if(! $this->stack) {
            return null;
        }

        return array_reduce($this->stack, function($build, $action) use ($units, $perUnit) {
            if(! in_array($action['method'], ['plus', 'minus'])) {
                return $this->applyStackAction($action, $build);
            }

            $argument = is_a($action['argument'] ?? null, RationalMoney::class)
                    ? $action['argument']
                    : Money::ofMinor($action['argument'] ?? 0, $build->getCurrency())->toRational();

            if($this->appliesPerUnit() && (! $perUnit) && $units > 1) {
                $argument = $argument->multipliedBy($units);
            }

            $action['argument'] = $argument;

            return $this->applyStackAction($action, $build);
        }, $build);
    }

    /**
     * Apply given stack action on the price being build
     */
    protected function applyStackAction(array $action, RationalMoney $build): RationalMoney
    {
        return call_user_func([$build, $action['method']], $action['argument'] ?? null);
    }
}
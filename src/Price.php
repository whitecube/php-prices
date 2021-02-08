<?php

namespace Whitecube\Price;

use Brick\Money\Money;
use Brick\Money\ISOCurrencyProvider;
use Brick\Math\RoundingMode;

class Price implements \JsonSerializable
{
    use Concerns\OperatesOnBase;
    use Concerns\ParsesPrices;
    use Concerns\HasUnits;
    use Concerns\HasVat;

    /**
     * The rounding methods that should be used when calculating prices
     *
     * @var array
     */
    static protected $rounding = [
        'exclusive' => RoundingMode::HALF_UP,
        'vat' => RoundingMode::HALF_UP,
    ];

    /**
     * The root price
     *
     * @var \Brick\Money\Money
     */
    protected $base;

    /**
     * The VAT definition
     *
     * @var null|\Whitecube\Price\Vat
     */
    protected $vat;

    /**
     * The quantity that needs to be applied to the base price
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
    private $calculator;

    /**
     * Create a new Price object
     *
     * @param \Brick\Money\Money $base
     * @param int $units
     * @return void
     */
    public function __construct(Money $base, $units = 1)
    {
        $this->base = $base;
        $this->setUnits($units);
    }

    /**
     * Convenience Money methods for creating Price objects
     *
     * @param string $method
     * @param array  $arguments
     * @return static
     */
    public static function __callStatic($method, $arguments)
    {
        try {
            $currency = ISOCurrencyProvider::getInstance()->getCurrency(strtoupper($method));
            $base = Money::ofMinor($arguments[0], $currency);
            $units = $arguments[1] ?? 1;
        } catch (\Exception $e) {
            $base = Money::$method(...$arguments);
            $units = 1;
        }

        return new static($base, $units);
    }

    /**
     * Configure the price rounding policy
     *
     * @param string $moment
     * @param int $method
     * @return void
     */
    public static function setRounding(string $moment, int $method)
    {
        static::$rounding[$moment] = $method;
    }

    /**
     * Get the current price rounding policy for given moment
     *
     * @param string $moment
     * @return int
     */
    public static function getRounding($moment)
    {
        return static::$rounding[$moment] ?? RoundingMode::UNNECESSARY;
    }

    /**
     * Return the price's underlying currency instance
     *
     * @return \Money\Currency
     */
    public function currency()
    {
        return $this->base->getCurrency();
    }

    /**
     * Return the price's base value
     *
     * @param bool $perUnit
     * @return \Brick\Money\Money
     */
    public function base($perUnit = true)
    {
        return ($perUnit)
            ? $this->base
            : $this->base->multipliedBy($this->units, static::getRounding('exclusive'));
    }

    /**
     * Return the EXCL. Money value
     *
     * @param bool $perUnit
     * @param bool $includeAfterVat
     * @return \Brick\Money\Money
     */
    public function exclusive($perUnit = false, $includeAfterVat = false)
    {
        $this->build();

        $amount = $this->calculator->exclusiveBeforeVat;

        if($includeAfterVat) {
            $amount = $amount->plus($this->calculator->exclusiveAfterVat, static::getRounding('exclusive'));
        }

        if($perUnit) {
            return $this->perUnit($amount);
        }

        return $amount;
    }

    /**
     * Return the INCL. Money value
     *
     * @param bool $perUnit
     * @return \Brick\Money\Money
     */
    public function inclusive($perUnit = false)
    {
        $this->build();

        $amount = $this->calculator->exclusiveBeforeVat;

        if($this->vat) {
            $amount = $amount->plus($this->vat->money(false), static::getRounding('vat'));
        }

        $amount = $amount->plus($this->calculator->exclusiveAfterVat, static::getRounding('exclusive'));

        if($perUnit) {
            return $this->perUnit($amount);
        }

        return $amount;
    }

    /**
     * Split given amount into ~equal parts and return the smallest
     *
     * @param \Brick\Money\Money $amount
     * @return \Brick\Money\Money
     */
    public function perUnit(Money $amount)
    {
        if($this->units === floatval(1)) {
            return $amount;
        }

        $parts = floor($this->units);

        $remainder = $amount->multipliedBy($this->units - $parts, RoundingMode::FLOOR);
        
        $allocated = $amount->minus($remainder);

        return Money::min(...$allocated->split($parts));
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

        $this->invalidate();

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
        $this->build();

        $modifications = $this->calculator->modifications;

        if(is_null($type)) {
            return array_values($modifications);
        }

        return array_values(array_filter($modifications, function($modification) use ($type) {
            return $modification['type'] === $type;
        }));
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
                return $value->plus($modifier, static::getRounding('exclusive'));
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
     * Get the defined modifiers from before or after the
     * VAT value should have been applied
     *
     * @param bool $postVat
     * @return array
     */
    public function getVatModifiers(bool $postVat)
    {
        return array_filter($this->modifiers, function($modifier) use ($postVat) {
            return $modifier->isBeforeVat() === !$postVat;
        });
    }

    /**
     * Reset the price calculator, forcing it to rebuild
     * next time a computed value is requested.
     *
     * @return void
     */
    public function invalidate()
    {
        $this->calculator = null;
    }

    /**
     * Launch the total price calculator only if
     * it does not yet exist or has been invalidated.
     *
     * @return void
     */
    public function build()
    {
        if(! is_null($this->calculator)) {
            return;
        }

        $this->calculator = new Calculator($this, $this->vat);
    }

    /**
     * Convert this price object into a readable 
     * total & inclusive money string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->inclusive()->__toString();
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function jsonSerialize()
    {
        $excl = $this->exclusive();
        $incl = $this->inclusive();

        return [
            'base' => $this->base->getMinorAmount(),
            'currency' => $this->base->getCurrency()->getCurrencyCode(),
            'units' => $this->units,
            'vat' => $this->vat->percentage(),
            'total' => [
                'exclusive' => $excl->getMinorAmount(),
                'inclusive' => $incl->getMinorAmount(),
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

        $base = Money::ofMinor($value['base'], $value['currency']);
        
        return (new static($base, $value['units']))
            ->setVat($value['vat']);
    }
}
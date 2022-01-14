<?php

namespace Whitecube\Price;

use Brick\Money\Money;
use Brick\Money\ISOCurrencyProvider;
use Brick\Math\RoundingMode;

class Price implements \JsonSerializable
{
    use Concerns\OperatesOnBase;
    use Concerns\ParsesPrices;
    use Concerns\FormatsPrices;
    use Concerns\HasUnits;
    use Concerns\HasVat;
    use Concerns\HasModifiers;

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
     * Convenience Money methods for creating Price objects and value formatting
     *
     * @param string $method
     * @param array  $arguments
     * @return static
     */
    public static function __callStatic($method, $arguments)
    {
        if(strpos($method, 'format') === 0) {
            return static::callFormatter(substr($method, 6), ...$arguments);
        }

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
     * Return the price's underlying context instance
     *
     * @return \Brick\Money\Context
     */
    public function context()
    {
        return $this->base->getContext();
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
            : $this->applyUnits($this->base);
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
        $result = $this->build()->exclusiveBeforeVat($perUnit ? true : false);

        if(! $includeAfterVat) {
            return $result['amount'];
        }

        $supplement = $this->build()->exclusiveAfterVat($perUnit ? true : false);

        return $result['amount']->plus($supplement['amount'], static::getRounding('exclusive'));
    }

    /**
     * Return the INCL. Money value
     *
     * @param bool $perUnit
     * @return \Brick\Money\Money
     */
    public function inclusive($perUnit = false)
    {
        $result = $this->build()->inclusive($perUnit ? true : false);

        return $result['amount'];
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
     * Multiply the given amount by the number of available units
     *
     * @param \Brick\Money\Money $amount
     * @param null|int $rounding
     * @return \Brick\Money\Money
     */
    public function applyUnits(Money $amount, int $rounding = null)
    {
        if(is_null($rounding)) {
            $rounding = static::getRounding('exclusive');
        }

        return $amount->multipliedBy($this->units, $rounding);
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
            return $this->calculator;
        }

        $this->calculator = new Calculator($this);

        return $this->calculator;
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
    public function jsonSerialize(): array
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
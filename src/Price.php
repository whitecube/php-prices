<?php

namespace Whitecube\Price;

use Brick\Money\Money;
use Brick\Money\ISOCurrencyProvider;
use Brick\Math\RoundingMode;
use Brick\Money\AbstractMoney;
use Brick\Money\Context;
use Brick\Money\Context\DefaultContext;
use Brick\Money\Currency;
use Whitecube\Price\Vat;

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
    static protected array $rounding = [
        'exclusive' => RoundingMode::HALF_UP,
        'vat' => RoundingMode::HALF_UP,
    ];

    /**
     * The root price
     */
    protected AbstractMoney $base;

    /**
     * The VAT definition
     */
    protected ?Vat $vat;

    /**
     * The price modifiers to apply
     */
    protected array $modifiers = [];

    /**
     * The modified results
     */
    private ?Calculator $calculator = null;

    /**
     * Create a new Price object
     */
    public function __construct(AbstractMoney $base, float|int|string $units = 1)
    {
        $this->base = $base;
        $this->setUnits($units);
    }

    /**
     * Convenience Money methods for creating Price objects and value formatting
     */
    public static function __callStatic(string $method, array $arguments): null|string|static
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
     */
    public static function setRounding(string $moment, int $method): void
    {
        static::$rounding[$moment] = $method;
    }

    /**
     * Get the current price rounding policy for given moment
     */
    public static function getRounding(string $moment): int
    {
        return static::$rounding[$moment] ?? RoundingMode::UNNECESSARY;
    }

    /**
     * Return the price's underlying currency instance
     */
    public function currency(): Currency
    {
        return $this->base->getCurrency();
    }

    /**
     * Return the price's underlying context instance
     */
    public function context(): ?Context
    {
        if (! method_exists($this->base, 'getContext')) {
            return null;
        }

        return $this->base->getContext();
    }

    /**
     * Return the price's base value
     */
    public function base(bool $perUnit = true): AbstractMoney
    {
        return ($perUnit)
            ? $this->base
            : $this->applyUnits($this->base);
    }

    /**
     * Return the EXCL. Money value
     */
    public function exclusive(bool $perUnit = false, bool $includeAfterVat = false): AbstractMoney
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
     */
    public function inclusive(bool $perUnit = false): AbstractMoney
    {
        $result = $this->build()->inclusive($perUnit ? true : false);

        return $result['amount'];
    }

    /**
     * Shorthand to easily get the underlying minor amount (as an integer)
     */
    public function toMinor(?string $version = null): int
    {
        if ($version === 'inclusive') {
            return $this->inclusive()->getMinorAmount()->toInt();
        }

        if ($version === 'exclusive') {
            return $this->exclusive()->getMinorAmount()->toInt();
        }

        return $this->base()->getMinorAmount()->toInt();
    }

    /**
     * Split given amount into ~equal parts and return the smallest
     */
    public function perUnit(AbstractMoney $amount): AbstractMoney
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
     */
    public function applyUnits(AbstractMoney $amount, ?int $rounding = null): AbstractMoney
    {
        if(is_null($rounding)) {
            $rounding = static::getRounding('exclusive');
        }

        return $amount->multipliedBy($this->units, $rounding);
    }

    /**
     * Reset the price calculator, forcing it to rebuild
     * next time a computed value is requested.
     */
    public function invalidate(): void
    {
        $this->calculator = null;
    }

    /**
     * Launch the total price calculator only if
     * it does not yet exist or has been invalidated.
     */
    public function build(): Calculator
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
     */
    public function __toString(): string
    {
        return $this->inclusive()->to(new DefaultContext, static::getRounding('vat'))->__toString();
    }

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
     * @throws \InvalidArgumentException
     */
    public static function json(string|array $value): static
    {
        if(is_string($value)) {
            $value = json_decode($value, true);
        }

        $base = Money::ofMinor($value['base'], $value['currency']);

        return (new static($base, $value['units']))
            ->setVat($value['vat']);
    }
}

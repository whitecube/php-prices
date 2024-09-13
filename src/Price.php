<?php

namespace Whitecube\Price;

use Brick\Money\Money;
use Brick\Money\ISOCurrencyProvider;
use Brick\Math\RoundingMode;
use Brick\Money\AbstractMoney;
use Brick\Money\RationalMoney;
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
     * The default rounding methods that should be applied when calculating prices
     */
    static protected ?int $defaultRoundingMode = null;

    /**
     * The default Money context that should be applied transforming Rational monies
     */
    static protected ?Context $defaultContext = null;

    /**
     * The root pricing value
     */
    protected RationalMoney $base;

    /**
     * The original pricing context
     */
    protected ?Context $context;

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
    public function __construct(AbstractMoney $base, float|int|string $units = 1, ?Context $context = null)
    {
        $this->base = $this->valueToRationalMoney($base);

        $this->context = ! is_null($context)
            ? $context
            : (method_exists($base, 'getContext') ? $base->getContext() : null);

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
     * Configure the default price rounding policy
     */
    public static function setDefaultRoundingMode(RoundingMode $roundingMode): void
    {
        static::$defaultRoundingMode = $roundingMode;
    }

    /**
     * Get the default price rounding policy
     */
    public static function getDefaultRoundingMode(): RoundingMode
    {
        return static::$defaultRoundingMode ?? RoundingMode::UNNECESSARY;
    }

    /**
     * Configure the default monies Context
     */
    public static function setDefaultContext(Context $context): void
    {
        static::$defaultContext = $context;
    }

    /**
     * Get the default price rounding policy
     */
    public static function getDefaultContext(): Context
    {
        return static::$defaultContext ?? new DefaultContext();
    }

    /**
     * Return the price's underlying currency instance
     */
    public function currency(): Currency
    {
        return $this->base->getCurrency();
    }

    /**
     * Define the price's Money Context
     */
    public function setContext(Context $context): static
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Return the price's Money context
     */
    public function context(): Context
    {
        return $this->context ?? static::getDefaultContext();
    }

    /**
     * Return the price's base amount as a Rational Money instance
     */
    public function base(bool $perUnit = true): RationalMoney
    {
        return ($perUnit)
            ? $this->base
            : $this->applyUnits($this->base);
    }

    /**
     * Return the price's base amount as a contextualized Money instance
     */
    public function baseMoney(bool $perUnit = true, ?RoundingMode $roundingMode = null): Money
    {
        return $this->toContext($this->base($perUnit), $roundingMode);
    }

    /**
     * Return the price's EXCL. amount as a Rational Money instance
     */
    public function exclusive(bool $perUnit = false, bool $includeAfterVat = false): RationalMoney
    {
        $result = $this->build()->exclusiveBeforeVat($perUnit ? true : false);

        if(! $includeAfterVat) {
            return $result['amount'];
        }

        $supplement = $this->build()->exclusiveAfterVat($perUnit ? true : false);

        return $result['amount']->plus($supplement['amount']);
    }

    /**
     * Return the price's EXCL. amount as a contextualized Money instance
     */
    public function exclusiveMoney(bool $perUnit = true, bool $includeAfterVat = false, ?RoundingMode $roundingMode = null): Money
    {
        return $this->toContext($this->exclusive($perUnit, $includeAfterVat), $roundingMode);
    }

    /**
     * Return the price's INCL. amount as a Rational Money instance
     */
    public function inclusive(bool $perUnit = false): RationalMoney
    {
        $result = $this->build()->inclusive($perUnit ? true : false);

        return $result['amount'];
    }

    /**
     * Return the price's EXCL. amount as a contextualized Money instance
     */
    public function inclusiveMoney(bool $perUnit = true, ?RoundingMode $roundingMode = null): Money
    {
        return $this->toContext($this->inclusive($perUnit), $roundingMode);
    }

    /**
     * Transform the provided Rational Money to a contextualized Money instance
     */
    protected function toContext(RationalMoney $value, ?RoundingMode $roundingMode = null): Money
    {
        return $value->to(
            $this->context(),
            $roundingMode ?? static::getDefaultRoundingMode()
        );
    }

    /**
     * Shorthand to easily get the underlying minor amount (as an integer)
     */
    public function toMinor(?string $version = null): int
    {
        if ($version === 'inclusive') {
            return $this->inclusiveMoney()->getMinorAmount()->toInt();
        }

        if ($version === 'exclusive') {
            return $this->exclusiveMoney()->getMinorAmount()->toInt();
        }

        return $this->baseMoney()->getMinorAmount()->toInt();
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
    public function applyUnits(RationalMoney $amount): RationalMoney
    {
        return $amount->multipliedBy($this->units);
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
     * Transform a given value into a Money instance
     */
    protected function valueToRationalMoney(BigNumber|int|float|string|AbstractMoney|Price $value, string $method = 'base'): RationalMoney
    {
        if(is_a($value, RationalMoney::class)) {
            return $value;
        }

        if(is_a($value, Money::class)) {
            return $value->toRational();
        }

        if(is_a($value, Price::class)) {
            return $value->$method();
        }

        return Money::ofMinor($value, $this->currency())->toRational();
    }

    /**
     * Convert this price object into a readable
     * total & inclusive money string
     */
    public function __toString(): string
    {
        return $this->inclusiveMoney()->__toString();
    }

    public function jsonSerialize(): array
    {
        return [
            'base' => $this->base->getAmount(),
            'currency' => $this->currency()->getCurrencyCode(),
            'units' => $this->units,
            'vat' => $this->vat?->percentage(),
            'total' => [
                'exclusive' => $this->exclusive()->getAmount(),
                'inclusive' => $this->inclusive()->getAmount(),
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

        $base = (strpos($value['base'], '/'))
            ? RationalMoney::of($value['base'], $value['currency'])
            : Money::ofMinor($value['base'], $value['currency']);

        return (new static($base, $value['units']))
            ->setVat($value['vat']);
    }
}

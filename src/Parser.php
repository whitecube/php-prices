<?php

namespace Whitecube\Price;

use Brick\Money\Currency;
use Brick\Money\ISOCurrencyProvider;

class Parser
{
    /**
     * The available parsable currency symbols
     */
    static protected ?array $symbols = null;

    /**
     * The original string value
     */
    protected string $original;

    /**
     * Create a new Parser object
     */
    public function __construct(string|int|float $value)
    {
        $this->original = strval($value);
    }

    /**
     * Find and transform the numeric value
     */
    public function extractValue(): string
    {
        $string = str_replace([',', ' ', ' '], ['.', '', ''], $this->original);

        preg_match('/^[^-\.\d]*(\-?\d+(?:\.\d+)?)[^\d]*$/', $string, $matches);

        if(!isset($matches[1])) {
            return 0;
        }

        [$digits, $decimals] = array_pad(explode('.', $matches[1]), 2, '0');

        $decimals = substr(strval(round(floatval('0.' . $decimals), 2)), 2);

        $value = ltrim($digits . str_pad($decimals, 2, '0'), '0');

        return strlen($value) ? $value : '0';
    }

    /**
     * Find the currency ISO-code
     */
    public function extractCurrency(): ?string
    {
        $symbols = static::getSymbols();

        $currencies = ISOCurrencyProvider::getInstance()->getAvailableCurrencies();

        foreach ($currencies as $currency) {
            $symbol = $symbols[$currency->getCurrencyCode()] ?? null;

            $pattern = $this->getCurrencyPattern($currency, $symbol);

            if(!preg_match($pattern, $this->original)) continue;

            return $currency->getCurrencyCode();
        }

        return null;
    }

    /**
     * Generate a Regex string for given currency
     */
    protected function getCurrencyPattern(Currency $currency, ?string $symbol = null): string
    {
        $pattern = '/^(?:(?:.*?[^\d]?\s)|(?:.*?\d))?(';
        $pattern .= $this->getEscapedPatternString($currency->getCurrencyCode());

        if($symbol) {
            $pattern .= '|';
            $pattern .= $this->getEscapedPatternString($symbol);
        }

        $pattern .= ')(?:(?:\s[^\d]?.*?)|(?:\d.*?))?$/';

        return $pattern;
    }

    /**
     * Escape each character for the given regex string
     */
    protected function getEscapedPatternString(string $search): string
    {
        $escaped = ['(',')','.',':','^','$','[',']','?','!','+','=','*',',','{','}','/','\\','-'];

        return implode('', array_map(function($char) use ($escaped) {
            if(!in_array($char, $escaped)) return $char;
            return '\\' . $char;
        }, str_split($search)));
    }

    /**
     * Get all the available currency symbols
     */
    static public function getSymbols(): array
    {
        if(is_null(static::$symbols)) {
            static::$symbols = static::loadSymbols();
        }

        return static::$symbols;
    }

    /**
     * Try to load the available currency symbols
     * @throws \RuntimeException
     */
    static protected function loadSymbols(): array
    {
        $file = __DIR__ . '/../resources/symbols.php';

        if (file_exists($file)) {
            return require $file;
        }

        throw new \RuntimeException('Failed to load currency symbols.');
    }
}

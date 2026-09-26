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

        $negative = str_starts_with($digits, '-');
        $whole = ltrim($digits, '-');
        if($whole === '') {
            $whole = '0';
        }

        $rounded = number_format(round(floatval('0.' . $decimals), 2), 2, '.', '');
        [$carry, $cents] = explode('.', $rounded);
        if($carry === '1') {
            $whole = $this->increment($whole);
        }

        $value = ltrim($whole . $cents, '0');
        if($value === '') {
            $value = '0';
        }
        if($negative && $value !== '0') {
            $value = '-' . $value;
        }

        return $value;
    }

    /**
     * Add one to a whole-unit digit string.
     */
    protected function increment(string $whole): string
    {
        $chars = str_split($whole);
        for($i = count($chars) - 1; $i >= 0; $i--) {
            if($chars[$i] !== '9') {
                $chars[$i] = (string) ((int) $chars[$i] + 1);

                return implode('', $chars);
            }
            $chars[$i] = '0';
        }

        return '1' . implode('', $chars);
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

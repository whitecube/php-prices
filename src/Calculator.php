<?php

namespace Whitecube\Price;

use Brick\Money\Money;
use Brick\Money\AbstractMoney;
use Whitecube\Price\Price;

class Calculator
{
    /**
     * The price configuration
     */
    private Price $price;

    /**
     * The previously calculated amounts
     */
    private array $cache = [];

    /**
     * Create a new calculator
     */
    public function __construct(Price $price)
    {
        $this->price = $price;
    }

    /**
     * Return the result for the "before VAT" Money build
     */
    public function exclusiveBeforeVat(bool $perUnit): array
    {
        return $this->getCached('exclusive_before_vat', $perUnit)
            ?? $this->setCached('exclusive_before_vat', $perUnit, $this->getExclusiveBeforeVatResult($perUnit));
    }

    /**
     * Return the result for the "VAT" Money build
     */
    public function vat(bool $perUnit): array|AbstractMoney
    {
        return $this->getCached('vat', $perUnit)
            ?? $this->setCached('vat', $perUnit, $this->getVatResult($perUnit));
    }

    /**
     * Return the result for the "after VAT" Money build
     */
    public function exclusiveAfterVat(bool $perUnit): array
    {
        return $this->getCached('exclusive_after_vat', $perUnit)
            ?? $this->setCached('exclusive_after_vat', $perUnit, $this->getExclusiveAfterVatResult($perUnit));
    }

    /**
     * Return the result for the complete Money build
     */
    public function inclusive(bool $perUnit): array
    {
        return $this->getCached('inclusive', $perUnit)
            ?? $this->setCached('inclusive', $perUnit, $this->getInclusiveResult($perUnit));
    }

    /**
     * Retrieve a cached value for key and unit mode
     */
    protected function getCached(string $key, bool $perUnit): null|array|AbstractMoney
    {
        return $this->cache[$key][$perUnit ? 'unit' : 'all'] ?? null;
    }

    /**
     * Set a cached value for key and unit mode
     */
    protected function setCached(string $key, bool $perUnit, array|AbstractMoney $result): array|AbstractMoney
    {
        $this->cache[$key][$perUnit ? 'unit' : 'all'] = $result;

        return $result;
    }

    /**
     * Compute the price for one unit before VAT is applied
     */
    protected function getExclusiveBeforeVatResult(bool $perUnit): array
    {
        $modifiers = $this->price->getVatModifiers(false);

        $result = [
            'amount' => $this->price->base($perUnit),
            'modifications' => [],
        ];

        return array_reduce($modifiers, function($result, $modifier) use ($perUnit) {
            return $this->applyModifier($modifier, $result, $perUnit);
        }, $result);
    }

    /**
     * Compute the price for one unit after VAT is applied
     */
    protected function getExclusiveAfterVatResult(bool $perUnit): array
    {
        $modifiers = $this->price->getVatModifiers(true);

        $exclusive = $this->exclusiveBeforeVat($perUnit)['amount'];
        
        $this->vat($perUnit);

        $vat = $this->price->vat();

        $result = [
            'amount' => Money::zero($this->price->currency(), $this->price->context()),
            'modifications' => [],
        ];

        return array_reduce($modifiers, function($result, $modifier) use ($perUnit, $exclusive, $vat) {
            return $this->applyModifier($modifier, $result, $perUnit, true, $exclusive, $vat);
        }, $result);
    }

    /**
     * Compute the VAT
     */
    protected function getVatResult(bool $perUnit): AbstractMoney
    {
        $vat = $this->price->vat(true);

        if(! $vat) {
            return Money::zero($this->price->currency(), $this->price->context());
        }

        $exclusive = $this->exclusiveBeforeVat($perUnit)['amount'];

        return $vat->apply($exclusive);
    }

    /**
     * Compute the complete price
     */
    protected function getInclusiveResult(bool $perUnit): array
    {
        $result = $this->exclusiveBeforeVat($perUnit);

        $result['amount'] = $result['amount']->plus($this->vat($perUnit), Price::getRounding('vat'));

        $supplement = $this->exclusiveAfterVat($perUnit);

        $result['amount'] = $result['amount']->plus($supplement['amount'], Price::getRounding('exclusive'));
        $result['modifications'] = array_merge($result['modifications'], $supplement['modifications']);

        return $result;
    }

    /**
     * Compute the price for one unit before VAT is applied
     */
    protected function applyModifier(PriceAmendable $modifier, array $result, bool $perUnit, bool $postVat = false, AbstractMoney $exclusive = null, Vat $vat = null): array
    {
        $updated = $modifier->apply($result['amount'], $this->price->units(), $perUnit, $exclusive, $vat);

        $result['modifications'][] = array_filter([
            'amount' => $updated ? $updated->minus($result['amount']) : null,
            'post' => $postVat,
            'type' => $modifier->type(),
            'key' => $modifier->key(),
            'attributes' => $modifier->attributes() ?: null,
        ]);

        if($updated) {
            $result['amount'] = $updated;
        }

        return $result;
    }
}
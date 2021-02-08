<?php

namespace Whitecube\Price;

use Brick\Money\Money;

class Calculator
{
    /**
     * The price configuration
     *
     * @var \Whitecube\Price\Price
     */
    private $price;

    /**
     * The previously calculated amounts
     *
     * @var array
     */
    private $cache = [];

    /**
     * Create a new calculator
     *
     * @param \Whitecube\Price\Price $price
     * @return void
     */
    public function __construct(Price $price)
    {
        $this->price = $price;
    }

    /**
     * Return the result for the "before VAT" Money build
     *
     * @param bool $perUnit
     * @return array
     */
    public function exclusiveBeforeVat($perUnit)
    {
        return $this->getCached('exclusive_before_vat', $perUnit)
            ?? $this->setCached('exclusive_before_vat', $perUnit, $this->getExclusiveBeforeVatResult($perUnit));
    }

    /**
     * Return the result for the "VAT" Money build
     *
     * @param bool $perUnit
     * @return \Brick\Money\Money
     */
    public function vat($perUnit)
    {
        return $this->getCached('vat', $perUnit)
            ?? $this->setCached('vat', $perUnit, $this->getVatResult($perUnit));
    }

    /**
     * Return the result for the "after VAT" Money build
     *
     * @param bool $perUnit
     * @return array
     */
    public function exclusiveAfterVat($perUnit)
    {
        return $this->getCached('exclusive_after_vat', $perUnit)
            ?? $this->setCached('exclusive_after_vat', $perUnit, $this->getExclusiveAfterVatResult($perUnit));
    }

    /**
     * Return the result for the complete Money build
     *
     * @param bool $perUnit
     * @return array
     */
    public function inclusive($perUnit)
    {
        return $this->getCached('inclusive', $perUnit)
            ?? $this->setCached('inclusive', $perUnit, $this->getInclusiveResult($perUnit));
    }

    /**
     * Retrieve a cached value for key and unit mode
     *
     * @param string $key
     * @param bool $perUnit
     * @return null|array
     */
    protected function getCached($key, $perUnit)
    {
        return $this->cache[$key][$perUnit ? 'unit' : 'all'] ?? null;
    }

    /**
     * Set a cached value for key and unit mode
     *
     * @param string $key
     * @param bool $perUnit
     * @param array $result
     * @return array
     */
    protected function setCached($key, $perUnit, $result)
    {
        $this->cache[$key][$perUnit ? 'unit' : 'all'] = $result;

        return $result;
    }

    /**
     * Compute the price for one unit before VAT is applied
     *
     * @param bool $perUnit
     * @return array
     */
    protected function getExclusiveBeforeVatResult($perUnit)
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
     *
     * @param bool $perUnit
     * @return array
     */
    protected function getExclusiveAfterVatResult($perUnit)
    {
        $modifiers = $this->price->getVatModifiers(true);

        $exclusive = $this->exclusiveBeforeVat($perUnit)['amount'];
        
        $this->vat($perUnit);

        $vat = $this->price->vat();

        $result = [
            'amount' => Money::zero($this->price->currency()),
            'modifications' => [],
        ];

        return array_reduce($modifiers, function($result, $modifier) use ($perUnit, $exclusive, $vat) {
            return $this->applyModifier($modifier, $result, $perUnit, true, $exclusive, $vat);
        }, $result);
    }

    /**
     * Compute the VAT
     *
     * @param bool $perUnit
     * @return \Brick\Money\Money
     */
    protected function getVatResult($perUnit)
    {
        $vat = $this->price->vat(false);

        if(! $vat) {
            return Money::zero($this->price->currency());
        }

        $exclusive = $this->exclusiveBeforeVat($perUnit)['amount'];

        return $vat->apply($exclusive);
    }

    /**
     * Compute the complete price
     *
     * @param bool $perUnit
     * @return array
     */
    protected function getInclusiveResult($perUnit)
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
     *
     * @param \Whitecube\Price\PriceAmendable $modifier
     * @param array $result
     * @param bool $perUnit
     * @param bool $postVat
     * @param null|\Brick\Money\Money $exclusive
     * @param null|\Whitecube\Price\Vat $vat
     * @return \Brick\Money\Money
     */
    protected function applyModifier(PriceAmendable $modifier, array $result, $perUnit, $postVat = false, Money $exclusive = null, Vat $vat = null)
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
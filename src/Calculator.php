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
     * The VAT configuration
     *
     * @var \Whitecube\Price\Vat
     */
    private $vat;

    /**
     * The exclusive amount without "after VAT" modifiers
     *
     * @var \Brick\Money\Money
     */
    public $exclusiveBeforeVat;

    /**
     * The exclusive "after VAT" amount
     *
     * @var \Brick\Money\Money
     */
    public $exclusiveAfterVat;

    /**
     * The applied modifiers history & results
     *
     * @var array
     */
    public $modifications = [];

    /**
     * Create a new calculator and launch it immediately
     *
     * @param \Whitecube\Price\Price $price
     * @param null|\Whitecube\Price\Vat $vat
     * @return void
     */
    public function __construct(Price $price, Vat $vat = null)
    {
        $this->price = $price;
        $this->vat = $vat;

        $this->exclusiveBeforeVat = $this->getBeforeVat();

        if($this->vat) {
            $this->vat->apply($this->exclusiveBeforeVat, $this->price);
        }

        $this->exclusiveAfterVat = $this->getAfterVat();
    }

    /**
     * Compute the price for one unit before VAT is applied
     *
     * @return \Brick\Money\Money
     */
    protected function getBeforeVat()
    {
        $modifiers = $this->price->getVatModifiers(false);

        return array_reduce($modifiers, function($amount, $modifier) {
            $modified = $this->applyModifier($modifier, $amount, $amount);

            return $this->insertModifier($amount, $modified);
        }, $this->price->base(false));
    }

    /**
     * Compute the price for one unit before VAT is applied
     *
     * @return \Brick\Money\Money
     */
    protected function getAfterVat()
    {
        $modifiers = $this->price->getVatModifiers(true);

        return array_reduce($modifiers, function($amount, $modifier) {
            $modified = $this->applyModifier($modifier, $amount, $this->exclusiveBeforeVat, $this->vat, true);

            return $this->insertModifier($amount, $modified);
        }, Money::zero($this->price->currency()));
    }

    /**
     * Compute the price for one unit before VAT is applied
     *
     * @param \Whitecube\Price\PriceAmendable $modifier
     * @param \Brick\Money\Money $build
     * @param \Brick\Money\Money $exclusive
     * @param null|\Whitecube\Price\Vat $vat
     * @param bool $postVat
     * @return \Brick\Money\Money
     */
    protected function applyModifier(PriceAmendable $modifier, Money $build, Money $exclusive, Vat $vat = null, $postVat = false)
    {
        return array_filter([
            'amount' => $modifier->apply($build, $exclusive, $vat),
            'post' => $postVat,
            'type' => $modifier->type(),
            'key' => $modifier->key(),
            'attributes' => $modifier->attributes() ?: null,
        ]);
    }

    /**
     * Compute the price for one unit before VAT is applied
     *
     * @return \Brick\Money\Money
     */
    protected function insertModifier(Money $amount, $modified)
    {
        $this->modifications[] = $modified;

        return $amount->plus($modified->amount());
    }
}
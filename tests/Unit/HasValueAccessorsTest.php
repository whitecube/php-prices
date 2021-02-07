<?php

namespace Tests\Unit;

use Brick\Money\Money;
use Brick\Money\Currency;
use Whitecube\Price\Price;

it('can convert to string displaying inclusive total amount', function() {
    $price = Price::EUR(100, 5)->setVat(10);

    expect($price->__toString())->toBe('EUR 5.50');
});

it('can access the currency object', function() {
    $price = Price::EUR(100);

    $currency = $price->currency();

    expect($currency)->toBeInstanceOf(Currency::class);
    expect($currency->getCurrencyCode())->toBe('EUR');
});
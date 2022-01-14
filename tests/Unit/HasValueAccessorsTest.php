<?php

namespace Tests\Unit;

use Brick\Money\Context;
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

it('can access the context object', function() {
    $price = Price::EUR(100);

    $context = $price->context();

    expect($context)->toBeInstanceOf(Context::class);
});

it('can access the base minor value', function() {
    $price = Price::EUR(100);

    $minor = $price->toMinor();

    expect($minor)->toBe(100);
});

it('can access the vat-inclusive minor value', function() {
    $price = Price::EUR(100)->setVat(21);

    $minor = $price->toMinor('inclusive');

    expect($minor)->toBe(121);
});

it('can access the vat-exclusive minor value', function() {
    $price = Price::EUR(100)->setVat(21);

    $minor = $price->toMinor('exclusive');

    expect($minor)->toBe(100);
});

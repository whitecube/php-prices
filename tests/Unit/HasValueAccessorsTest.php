<?php

namespace Tests\Unit;

use Brick\Money\Money;
use Brick\Money\Currency;
use Whitecube\Price\Price;

// TODO : Define what to do with these tests

it('can access the currency object', function() {
    $instance = Price::EUR(100);

    $currency = $instance->currency();

    expect($currency)->toBeInstanceOf(Currency::class);
    expect($currency->getCurrencyCode())->toBe('EUR');
});
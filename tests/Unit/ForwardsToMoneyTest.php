<?php

namespace Tests\Unit;

use Brick\Money\Money;
use Whitecube\Price\Price;

it('creates instance from Money API', function() {
    $price = Price::ofMinor(500, 'EUR');

    expect($price)->toBeInstanceOf(Price::class);
    expect($price->base())->toBeInstanceOf(Money::class);
    expect($price->units())->toBe(floatval(1));
});

it('forwards modifications to base Money instance', function() {
    $price = Price::of(5, 'EUR');

    $price->minus(2);
    $price->plus(Price::ofMinor(100, 'EUR'));

    expect($price->equals(4))->toBeTrue();
});

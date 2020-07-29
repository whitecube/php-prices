<?php

namespace Tests\Unit;

use Money\Money;
use Whitecube\Price\Price;

it('can access the base amount', function() {
    $instance = Price::EUR(500, 5);

    assertEquals('500', $instance->baseAmount());
    assertEquals('2500', $instance->baseAmount(false));
});

it('can access the exclusive amount', function() {
    $instance = Price::EUR(600, 5)
        ->addDiscount(function(Money $value) {
            return $value->subtract(Money::EUR(100));
        })
        ->setVat(10);

    assertEquals('500', $instance->exclusiveAmount());
    assertEquals('2500', $instance->exclusiveAmount(false));

    assertEquals('500', $instance->amount());
    assertEquals('2500', $instance->amount(false));
});

it('can access the inclusive amount', function() {
    $instance = Price::EUR(600, 5)
        ->addDiscount(function(Money $value) {
            return $value->subtract(Money::EUR(100));
        }, 'foo', true)
        ->setVat(10);

    assertEquals('550', $instance->inclusiveAmount());
    assertEquals('2750', $instance->inclusiveAmount(false));
});
<?php

namespace Tests\Unit;

use Money\Money;
use Whitecube\Price\Price;

it('creates instance from Money API', function() {
    $instance = Price::EUR(500, 5);

    assertInstanceOf(Price::class, $instance);
    assertInstanceOf(Money::class, $instance->base());
    assertEquals(5, $instance->units());
});

it('forwards modifications to base Money instance', function() {
    $money = Money::EUR(500);
    $price = new Price($money);

    $price->subtract(Money::EUR(100));

    assertTrue($price->equals(Money::EUR(400)));
});

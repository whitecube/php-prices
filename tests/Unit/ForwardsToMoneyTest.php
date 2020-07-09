<?php

namespace Tests\Unit;

use Money\Money;
use Whitecube\Price\Price;

it('creates instance from Money API', function() {
    $instance = Price::EUR(500, 5);

    $this->assertInstanceOf(Price::class, $instance);
    $this->assertInstanceOf(Money::class, $instance->base());
    $this->assertEquals(5, $instance->units());
});

it('forwards modifications to base Money instance', function() {
    $money = Money::EUR(500);
    $price = new Price($money);

    $price->subtract(Money::EUR(100));

    $this->assertTrue($price->equals(Money::EUR(400)));
});
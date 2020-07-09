<?php

namespace Tests\Unit;

use Money\Money;
use Whitecube\Price\Price;

it('sets units during instantiation', function() {
    $base = Money::EUR(500);

    $one = new Price($base);
    $three = new Price($base, 3);

    $this->assertEquals(1, $one->units());
    $this->assertEquals(3, $three->units());
});

it('sets units using the setUnits method', function() {
    $base = Money::EUR(500);
    $instance = new Price($base);

    $this->assertInstanceOf(Price::class, $instance->setUnits(5));
    $this->assertEquals(5, $instance->units());

    $this->assertInstanceOf(Price::class, $instance->setUnits(1.75));
    $this->assertEquals(1.75, $instance->units());

    $this->assertInstanceOf(Price::class, $instance->setUnits('2,485'));
    $this->assertEquals(2.485, $instance->units());
});

it('returns base price per unit by default', function() {
    $base = Money::EUR(500);
    $instance = new Price($base, 3);

    $this->assertTrue($instance->base()->equals(Money::EUR(500)));
    // Passing "false" to base() should return the price for all units
    $this->assertTrue($instance->base(false)->equals(Money::EUR(1500)));
});
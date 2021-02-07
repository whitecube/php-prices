<?php

namespace Tests\Unit;

use Brick\Money\Money;
use Whitecube\Price\Price;

it('sets units during instantiation', function() {
    $base = Money::ofMinor(500, 'EUR');

    $one = new Price($base);
    $three = new Price($base, 3);

    expect($one->units())->toBe(floatval(1));
    expect($three->units())->toBe(floatval(3));
});

it('sets units using the setUnits method', function() {
    $base = Money::ofMinor(500, 'EUR');
    $instance = new Price($base);

    expect($instance->setUnits(5))->toBeInstanceOf(Price::class);
    expect($instance->units())->toBe(floatval(5));

    expect($instance->setUnits(1.75))->toBeInstanceOf(Price::class);
    expect($instance->units())->toBe(floatval(1.75));

    expect($instance->setUnits('2,485'))->toBeInstanceOf(Price::class);
    expect($instance->units())->toBe(floatval(2.485));
});

it('returns base price per unit by default', function() {
    $base = Money::ofMinor(500, 'EUR');
    $instance = new Price($base, 3);

    expect($instance->base()->getAmount()->compareTo(Money::ofMinor(500, 'EUR')->getAmount()))
        ->toBe(0);

    // Passing "false" to base() should return the price for all units
    expect($instance->base(false)->getAmount()->compareTo(Money::ofMinor(1500, 'EUR')->getAmount()))
        ->toBe(0);
});
<?php

namespace Tests\Unit;

use Brick\Money\Money;
use Whitecube\Price\Price;

it('sets units using the setUnits method', function() {
    $instance = Price::EUR(500);

    expect($instance->setUnits(5))->toBeInstanceOf(Price::class);
    expect($instance->units())->toBe(floatval(5));

    expect($instance->setUnits(1.75))->toBeInstanceOf(Price::class);
    expect($instance->units())->toBe(floatval(1.75));

    expect($instance->setUnits('2,485'))->toBeInstanceOf(Price::class);
    expect($instance->units())->toBe(floatval(2.485));
});
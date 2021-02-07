<?php

namespace Tests\Unit;

use Brick\Money\Money;
use Whitecube\Price\Price;

it('forwards modifications to base Money instance', function() {
    $price = new Price(Money::of(5, 'EUR'), 1);

    $price->minus('2.00')   // €3.00
        ->plus('1.50')      // €4.50
        ->dividedBy(2)      // €2.25
        ->multipliedBy(-3)  // €-6.75
        ->abs();            // €6.75

    expect($price->__toString())->toBe('EUR 6.75');
});

it('compares value to final amount', function() {
    $price = new Price(Money::ofMinor(500, 'EUR'), 2);

    $less = 999;
    $same = 1000;
    $more = 1001;

    expect($price->compareTo($less))->toBe(1);
    expect($price->compareTo($same))->toBe(0);
    expect($price->compareTo($more))->toBe(-1);

    $string = '1000';
    $money = Money::ofMinor(1000, 'EUR');
    $other = Price::EUR(250, 4);

    expect($price->compareTo($string))->toBe(0);
    expect($price->compareTo($money))->toBe(0);
    expect($price->compareTo($other))->toBe(0);
});

it('compares value to base amount', function() {
    $price = new Price(Money::ofMinor(500, 'EUR'), 2);

    $less = 499;
    $same = 500;
    $more = 501;

    expect($price->compareBaseTo($less))->toBe(1);
    expect($price->compareBaseTo($same))->toBe(0);
    expect($price->compareBaseTo($more))->toBe(-1);

    $string = '500';
    $money = Money::ofMinor(500, 'EUR');
    $other = Price::EUR(500, 4);

    expect($price->compareBaseTo($string))->toBe(0);
    expect($price->compareBaseTo($money))->toBe(0);
    expect($price->compareBaseTo($other))->toBe(0);
});

it('cannot compare unmatching currencies', function() {
    Price::EUR(500)->compareTo(Price::USD(500));
})->throws(\Brick\Money\Exception\MoneyMismatchException::class);

it('checks final amount equality', function() {
    $price = new Price(Money::ofMinor(500, 'EUR'), 2);

    $less = 999;
    $same = 1000;
    $more = 1001;

    expect($price->equals($less))->toBeFalse();
    expect($price->equals($same))->toBeTrue();
    expect($price->equals($more))->toBeFalse();

    $string = '1000';
    $money = Money::ofMinor(1000, 'EUR');
    $other = Price::EUR(250, 4);

    expect($price->equals($string))->toBeTrue();
    expect($price->equals($money))->toBeTrue();
    expect($price->equals($other))->toBeTrue();
});
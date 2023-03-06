<?php

namespace Tests\Unit;

use Brick\Money\Money;
use Whitecube\Price\Price;

it('creates instances from constructor with one default unit', function() {
    $money = Money::ofMinor(500, 'EUR');

    $price = new Price($money);

    expect($price->__toString())->toBe('EUR 5.00');
    expect($price->units())->toBe(floatval(1));
});

it('creates instances from constructor with given units', function() {
    $money = Money::ofMinor(500, 'EUR');

    $price = new Price($money, 5);

    expect($price->__toString())->toBe('EUR 25.00');
    expect($price->units())->toBe(floatval(5));
});

it('creates instances from currency code static method with one default unit', function() {
    $uppercase = Price::EGP(100);

    expect($uppercase)->toBeInstanceOf(Price::class);
    expect($uppercase->__toString())->toBe('EGP 1.00');
    expect($uppercase->units())->toBe(floatval(1));

    $lowercase = Price::egp(200);

    expect($lowercase)->toBeInstanceOf(Price::class);
    expect($lowercase->__toString())->toBe('EGP 2.00');
    expect($lowercase->units())->toBe(floatval(1));
});

it('creates instances from currency code static method with given units', function() {
    $uppercase = Price::JMD(100, 5);

    expect($uppercase)->toBeInstanceOf(Price::class);
    expect($uppercase->__toString())->toBe('JMD 5.00');
    expect($uppercase->units())->toBe(floatval(5));

    $lowercase = Price::jmd(200, 5);

    expect($lowercase)->toBeInstanceOf(Price::class);
    expect($lowercase->__toString())->toBe('JMD 10.00');
    expect($lowercase->units())->toBe(floatval(5));
});

it('cannot create instances from unexisting currency code', function() {
    $wrong = Price::ZZZ(100, 5);
})->throws(\Error::class);

it('creates instances from static Money API methods with one default unit', function() {
    $minor = Price::ofMinor(500, 'EUR');

    expect($minor)->toBeInstanceOf(Price::class);
    expect($minor->__toString())->toBe('EUR 5.00');
    expect($minor->units())->toBe(floatval(1));

    $major = Price::of(6, 'EUR');

    expect($major)->toBeInstanceOf(Price::class);
    expect($major->__toString())->toBe('EUR 6.00');
    expect($major->units())->toBe(floatval(1));
});

it('creates instances from parsed string with one default unit', function() {
    $price = Price::parse('2.20', 'EUR');

    expect($price)->toBeInstanceOf(Price::class);
    expect($price->__toString())->toBe('EUR 2.20');
    expect($price->units())->toBe(floatval(1));
});

it('creates instances from parsed string with given units', function() {
    $price = Price::parse('2.20', 'EUR', 4);

    expect($price)->toBeInstanceOf(Price::class);
    expect($price->__toString())->toBe('EUR 8.80');
    expect($price->units())->toBe(floatval(4));
});

it('creates instances from rational money', function() {
    $money = Money::ofMinor(500, 'EUR')->toRational()->dividedBy(3);

    $price = new Price($money);

    expect($price->inclusive()->simplified()->__toString())->toBe('EUR 5/3');
    expect($price->__toString())->toBe('EUR 1.67');
});

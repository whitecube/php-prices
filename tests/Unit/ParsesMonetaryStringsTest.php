<?php

namespace Tests\Unit;

use Brick\Money\Money;
use Whitecube\Price\Price;

it('parses decimal (point) currency values', function() {
    expect(Price::parse('$1.10')->equals(Money::of(1.1,'ARS')))->toBeTrue();
    expect(Price::parse('$ 2.20')->equals(Money::of(2.2,'ARS')))->toBeTrue();
    expect(Price::parse('$ - 3.30')->equals(Money::of(-3.3,'ARS')))->toBeTrue();
    expect(Price::parse('4.40$')->equals(Money::of(4.4,'ARS')))->toBeTrue();
    expect(Price::parse('5.50 $')->equals(Money::of(5.5,'ARS')))->toBeTrue();
    expect(Price::parse('6.60 - $')->equals(Money::of(6.6,'ARS')))->toBeTrue();
});

it('parses decimal (comma) currency values', function() {
    expect(Price::parse('€1,10')->equals(Money::of(1.1,'EUR')))->toBeTrue();
    expect(Price::parse('€ 2,20')->equals(Money::of(2.2,'EUR')))->toBeTrue();
    expect(Price::parse('€ - 3,30')->equals(Money::of(-3.3,'EUR')))->toBeTrue();
    expect(Price::parse('4,40€')->equals(Money::of(4.4,'EUR')))->toBeTrue();
    expect(Price::parse('5,50 €')->equals(Money::of(5.5,'EUR')))->toBeTrue();
    expect(Price::parse('6,60 - €')->equals(Money::of(6.6,'EUR')))->toBeTrue();
});

it('parses decimal values into requested currency', function() {
    expect(Price::parse('$1,10', 'EUR')->equals(Money::of(1.1,'EUR')))->toBeTrue();
    expect(Price::parse('2.20', 'EUR')->equals(Money::of(2.2,'EUR')))->toBeTrue();
});

it('parses uncommon decimal values', function() {
    expect(Price::parse('1', 'EUR')->equals(Money::of(1,'EUR')))->toBeTrue();
    expect(Price::parse('2.2', 'EUR')->equals(Money::of(2.2,'EUR')))->toBeTrue();
    expect(Price::parse('3.003', 'EUR')->equals(Money::of(3,'EUR')))->toBeTrue();
    expect(Price::parse('4.005', 'EUR')->equals(Money::of(4.01,'EUR')))->toBeTrue();
    expect(Price::parse('foo', 'EUR')->equals(Money::of(0,'EUR')))->toBeTrue();
    expect(Price::parse('bar 6.50', 'EUR')->equals(Money::of(6.5,'EUR')))->toBeTrue();
});

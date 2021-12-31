<?php

namespace Tests\Unit;

use Whitecube\Price\Price;

it('parses decimal (point) currency values', function() {
    expect(Price::parse('$1.10')->__toString())->toBe('ARS 1.10');
    expect(Price::parse('$ 2.20')->__toString())->toBe('ARS 2.20');
    expect(Price::parse('$ - 3.30')->__toString())->toBe('ARS -3.30');
    expect(Price::parse('4.40$')->__toString())->toBe('ARS 4.40');
    expect(Price::parse('5.50 $')->__toString())->toBe('ARS 5.50');
    expect(Price::parse('6.60 - $')->__toString())->toBe('ARS 6.60');
});

it('parses decimal (comma) currency values', function() {
    expect(Price::parse('€1,10')->__toString())->toBe('EUR 1.10');
    expect(Price::parse('€ 2,20')->__toString())->toBe('EUR 2.20');
    expect(Price::parse('€ - 3,30')->__toString())->toBe('EUR -3.30');
    expect(Price::parse('4,40€')->__toString())->toBe('EUR 4.40');
    expect(Price::parse('5,50 €')->__toString())->toBe('EUR 5.50');
    expect(Price::parse('6,60 - €')->__toString())->toBe('EUR 6.60');
});

it('parses decimal values into requested currency', function() {
    expect(Price::parse('$1,10', 'EUR')->__toString())->toBe('EUR 1.10');
    expect(Price::parse('2.20', 'EUR')->__toString())->toBe('EUR 2.20');
});

it('parses uncommon decimal values', function() {
    expect(Price::parse('1', 'EUR')->__toString())->toBe('EUR 1.00');
    expect(Price::parse('2.2', 'EUR')->__toString())->toBe('EUR 2.20');
    expect(Price::parse('3.003', 'EUR')->__toString())->toBe('EUR 3.00');
    expect(Price::parse('4.005', 'EUR')->__toString())->toBe('EUR 4.01');
    expect(Price::parse('5.008', 'EUR')->__toString())->toBe('EUR 5.01');
    expect(Price::parse('foo', 'EUR')->__toString())->toBe('EUR 0.00');
    expect(Price::parse('bar 6.50', 'EUR')->__toString())->toBe('EUR 6.50');
});

it('parses values with both narrow non-breaking and regular spaces', function() {
    expect(Price::parse('1 234,56', 'EUR')->__toString())->toBe('EUR 1234.56');
    expect(Price::parse('1 234,56', 'EUR')->__toString())->toBe('EUR 1234.56');
});

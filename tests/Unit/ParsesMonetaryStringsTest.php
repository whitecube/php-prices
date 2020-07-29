<?php

namespace Tests\Unit;

use Money\Money;
use Whitecube\Price\Price;

it('parses decimal (point) currency values', function() {
    assertTrue(Money::USD(110)->equals(Price::parseCurrency('$1.10')->exclusive()));
    assertTrue(Money::USD(220)->equals(Price::parseCurrency('$ 2.20')->exclusive()));
    assertTrue(Money::USD(-330)->equals(Price::parseCurrency('$ - 3.30')->exclusive()));
    assertTrue(Money::USD(440)->equals(Price::parseCurrency('4.40$')->exclusive()));
    assertTrue(Money::USD(550)->equals(Price::parseCurrency('5.50 $')->exclusive()));
    assertTrue(Money::USD(660)->equals(Price::parseCurrency('6.60 - $')->exclusive()));
});

it('parses decimal (comma) currency values', function() {
    assertTrue(Money::EUR(110)->equals(Price::parseCurrency('€1,10')->exclusive()));
    assertTrue(Money::EUR(220)->equals(Price::parseCurrency('€ 2,20')->exclusive()));
    assertTrue(Money::EUR(-330)->equals(Price::parseCurrency('€ - 3,30')->exclusive()));
    assertTrue(Money::EUR(440)->equals(Price::parseCurrency('4,40€')->exclusive()));
    assertTrue(Money::EUR(550)->equals(Price::parseCurrency('5,50 €')->exclusive()));
    assertTrue(Money::EUR(660)->equals(Price::parseCurrency('6,60 - €')->exclusive()));
});

it('parses decimal values into requested currency', function() {
    assertTrue(Money::EUR(110)->equals(Price::parseEUR('$1,10')->exclusive()));
    assertTrue(Money::EUR(220)->equals(Price::parseEUR('2.20')->exclusive()));
});

it('parses uncommon decimal values', function() {
    assertTrue(Money::EUR(100)->equals(Price::parseEUR('1')->exclusive()));
    assertTrue(Money::EUR(220)->equals(Price::parseEUR('2.2')->exclusive()));
    assertTrue(Money::EUR(300)->equals(Price::parseEUR('3.003')->exclusive()));
    assertTrue(Money::EUR(401)->equals(Price::parseEUR('4.005')->exclusive()));
    assertTrue(Money::EUR(0)->equals(Price::parseEUR('foo')->exclusive()));
    assertTrue(Money::EUR(650)->equals(Price::parseEUR('bar 6.50')->exclusive()));
});
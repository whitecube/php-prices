<?php

namespace Tests\Unit;

use Money\Money;
use Whitecube\Price\Price;

it('sets VAT from relative percentage', function() {
    $instance = Price::EUR(200);

    assertInstanceOf(Price::class, $instance->setVat(22.5));
    assertTrue($instance->vatPercentage() === 22.5);
    assertTrue($instance->vat()->equals(Money::EUR(45)));

    assertInstanceOf(Price::class, $instance->setVat(10));
    assertTrue($instance->vatPercentage() === 10.0);
    assertTrue($instance->vat()->equals(Money::EUR(20)));

    assertInstanceOf(Price::class, $instance->setVat('32,00 %'));
    assertTrue($instance->vatPercentage() === 32.0);
    assertTrue($instance->vat()->equals(Money::EUR(64)));
});

it('sets VAT from Money value', function() {
    $instance = Price::EUR(200);
    $vat = Money::EUR(50);

    assertInstanceOf(Price::class, $instance->setVat($vat));
    assertTrue($instance->vatPercentage() === 25.0);
    assertTrue($instance->vat()->equals($vat));
});

it('unsets VAT when given null', function() {
    $instance = Price::EUR(200)->setVat(6);

    assertInstanceOf(Price::class, $instance->setVat(null));
    assertTrue(is_null($instance->vatPercentage()));
    assertTrue(is_null($instance->vat()));
});

it('returns VAT for all units by default', function() {
    $instance = Price::EUR(500, 3)->setVat(10);

    assertTrue($instance->vat()->equals(Money::EUR(150)));
    // Passing "true" to vat() should return the VAT value per unit
    assertTrue($instance->vat(true)->equals(Money::EUR(50)));
});

it('returns exclusive amount without VAT for all units', function() {
    $instance = Price::EUR(500, 3);

    assertTrue($instance->exclusive()->equals(Money::EUR(1500)));
    // Passing "true" to exclusive() should return the price per unit
    assertTrue($instance->exclusive(true)->equals(Money::EUR(500)));

    $instance->setVat(10);

    assertTrue($instance->exclusive()->equals(Money::EUR(1500)));
    // Passing "true" to exclusive() should return the price per unit
    assertTrue($instance->exclusive(true)->equals(Money::EUR(500)));
});

it('returns inclusive amount with VAT when defined', function() {
    $instance = Price::EUR(500, 3);

    assertTrue($instance->inclusive()->equals(Money::EUR(1500)));
    // Passing "true" to inclusive() should return the price per unit
    assertTrue($instance->inclusive(true)->equals(Money::EUR(500)));

    $instance->setVat(10);

    assertTrue($instance->inclusive()->equals(Money::EUR(1650)));
    // Passing "true" to inclusive() should return the price per unit
    assertTrue($instance->inclusive(true)->equals(Money::EUR(550)));
});


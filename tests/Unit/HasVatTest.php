<?php

namespace Tests\Unit;

use Money\Money;
use Whitecube\Price\Price;

it('sets VAT from relative percentage', function() {
    $instance = Price::EUR(200);

    $this->assertInstanceOf(Price::class, $instance->setVat(22.5));
    $this->assertTrue($instance->vatPercentage() === 22.5);
    $this->assertTrue($instance->vat()->equals(Money::EUR(45)));

    $this->assertInstanceOf(Price::class, $instance->setVat(10));
    $this->assertTrue($instance->vatPercentage() === 10.0);
    $this->assertTrue($instance->vat()->equals(Money::EUR(20)));

    $this->assertInstanceOf(Price::class, $instance->setVat('32,00 %'));
    $this->assertTrue($instance->vatPercentage() === 32.0);
    $this->assertTrue($instance->vat()->equals(Money::EUR(64)));
});

it('sets VAT from Money value', function() {
    $instance = Price::EUR(200);
    $vat = Money::EUR(50);

    $this->assertInstanceOf(Price::class, $instance->setVat($vat));
    $this->assertTrue($instance->vatPercentage() === 25.0);
    $this->assertTrue($instance->vat()->equals($vat));
});

it('unsets VAT when given null', function() {
    $instance = Price::EUR(200)->setVat(6);

    $this->assertInstanceOf(Price::class, $instance->setVat(null));
    $this->assertTrue(is_null($instance->vatPercentage()));
    $this->assertTrue(is_null($instance->vat()));
});

it('returns VAT for all units by default', function() {
    $instance = Price::EUR(500, 3)->setVat(10);

    $this->assertTrue($instance->vat()->equals(Money::EUR(150)));
    // Passing "true" to vat() should return the VAT value per unit
    $this->assertTrue($instance->vat(true)->equals(Money::EUR(50)));
});

it('returns exclusive amount without VAT for all units', function() {
    $instance = Price::EUR(500, 3);

    $this->assertTrue($instance->exclusive()->equals(Money::EUR(1500)));
    // Passing "true" to exclusive() should return the price per unit
    $this->assertTrue($instance->exclusive(true)->equals(Money::EUR(500)));

    $instance->setVat(10);

    $this->assertTrue($instance->exclusive()->equals(Money::EUR(1500)));
    // Passing "true" to exclusive() should return the price per unit
    $this->assertTrue($instance->exclusive(true)->equals(Money::EUR(500)));
});

it('returns inclusive amount with VAT when defined', function() {
    $instance = Price::EUR(500, 3);

    $this->assertTrue($instance->inclusive()->equals(Money::EUR(1500)));
    // Passing "true" to inclusive() should return the price per unit
    $this->assertTrue($instance->inclusive(true)->equals(Money::EUR(500)));

    $instance->setVat(10);

    $this->assertTrue($instance->inclusive()->equals(Money::EUR(1650)));
    // Passing "true" to inclusive() should return the price per unit
    $this->assertTrue($instance->inclusive(true)->equals(Money::EUR(550)));
});


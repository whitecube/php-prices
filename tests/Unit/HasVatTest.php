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

it('returns exclusive amount without VAT', function() {
    $instance = Price::EUR(200);

    $this->assertTrue($instance->exclusive()->equals(Money::EUR(200)));

    $instance->setVat(21);

    $this->assertTrue($instance->exclusive()->equals(Money::EUR(200)));
});

it('returns inclusive amount with VAT when defined', function() {
    $instance = Price::EUR(200);

    $this->assertTrue($instance->inclusive()->equals(Money::EUR(200)));

    $instance->setVat(21);

    $this->assertTrue($instance->inclusive()->equals(Money::EUR(242)));
});


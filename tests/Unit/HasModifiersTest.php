<?php

namespace Tests\Unit;

use Money\Money;
use Whitecube\Price\Price;
use Whitecube\Price\Modifier;
use Tests\Fixtures\AmendableModifier;
use Tests\Fixtures\NonAmendableModifier;
use Tests\Fixtures\CustomAmendableModifier;

it('can add a callable modifier', function() {
    $price = Price::EUR(500)->addModifier(function(Money $value) {
        return $value->multiply(1.5);
    });

    $this->assertInstanceOf(Price::class, $price);

    $this->assertTrue(Money::EUR(750)->equals($price->exclusive()));
});

it('can add a modifier instance', function() {
    $callback = function(Money $value) {
        return $value->multiply(2);
    };

    $modifier = new Modifier($callback);

    $price = Price::EUR(500)->addModifier($modifier);

    $this->assertInstanceOf(Price::class, $price);

    $this->assertTrue(Money::EUR(1000)->equals($price->exclusive()));
});

it('can add a custom amendable modifier instance', function() {
    $modifier = new AmendableModifier();

    $price = Price::EUR(500)->addModifier($modifier);

    $this->assertInstanceOf(Price::class, $price);

    $this->assertTrue(Money::EUR(625)->equals($price->exclusive()));
});

it('can add a custom amendable modifier classname', function() {
    $price = Price::EUR(500)->addModifier(AmendableModifier::class);

    $this->assertInstanceOf(Price::class, $price);

    $this->assertTrue(Money::EUR(625)->equals($price->exclusive()));
});

it('can add a custom amendable modifier classname with custom arguments', function() {
    $price = Price::EUR(500)->addModifier(CustomAmendableModifier::class, Money::EUR(250));

    $this->assertInstanceOf(Price::class, $price);

    $this->assertTrue(Money::EUR(750)->equals($price->exclusive()));
});

it('can add a numeric modifier', function() {
    $price = Price::EUR(500)->addModifier('-100,00');

    $this->assertInstanceOf(Price::class, $price);

    $this->assertTrue(Money::EUR(400)->equals($price->exclusive()));
});

it('can add a Money modifier instance', function() {
    $price = Price::EUR(500)->addModifier(Money::EUR(150));

    $this->assertInstanceOf(Price::class, $price);

    $this->assertTrue(Money::EUR(650)->equals($price->exclusive()));
});

it('cannot add a NULL modifier', function() {
    $this->expectException(\InvalidArgumentException::class);

    Price::EUR(500)->addModifier(null);
});

it('cannot add an invalid modifier', function() {
    $this->expectException(\InvalidArgumentException::class);

    Price::EUR(500)->addModifier(['something','unusable']);
});

it('cannot add a non-PriceAmendable modifier instance', function() {
    $this->expectException(\InvalidArgumentException::class);

    $modifier = new NonAmendableModifier();

    Price::EUR(500)->addModifier($modifier);
});

it('can add a tax modifier', function() {
    $price = Price::EUR(500)->addTax(function(Money $value) {
        return $value->add(Money::EUR(50));
    });

    $this->assertInstanceOf(Price::class, $price);

    $this->assertTrue(Money::EUR(550)->equals($price->exclusive()));
});

it('can add a discount modifier', function() {
    $price = Price::EUR(500)->addDiscount(function(Money $value) {
        return $value->subtract(Money::EUR(50));
    });

    $this->assertInstanceOf(Price::class, $price);

    $this->assertTrue(Money::EUR(450)->equals($price->exclusive()));
});
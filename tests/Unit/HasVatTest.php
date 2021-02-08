<?php

namespace Tests\Unit;

use Brick\Money\Money;
use Whitecube\Price\Price;

it('sets VAT from relative percentage', function() {
    $instance = Price::ofMinor(200, 'EUR');

    expect($instance->setVat(22.5))->toBeInstanceOf(Price::class);
    expect($instance->vat()->getPercentage() === 22.5)->toBeTrue();
    expect($instance->vat()->getAmount()->compareTo(Money::ofMinor(45, 'EUR')->getAmount()))->toBe(0);

    expect($instance->setVat(10))->toBeInstanceOf(Price::class);
    expect($instance->vat()->getPercentage() === 10.0)->toBeTrue();
    expect($instance->vat()->getAmount()->compareTo(Money::ofMinor(20, 'EUR')->getAmount()))->toBe(0);

    expect($instance->setVat('32,00 %'))->toBeInstanceOf(Price::class);
    expect($instance->vat()->getPercentage() === 32.0)->toBeTrue();
    expect($instance->vat()->getAmount()->compareTo(Money::ofMinor(64, 'EUR')->getAmount()))->toBe(0);
});

it('unsets VAT when given null', function() {
    $instance = Price::ofMinor(200, 'EUR')->setVat(6);

    expect($instance->setVat(null))->toBeInstanceOf(Price::class);
    expect(is_null($instance->vat()))->toBeTrue();
});

it('returns VAT for all units by default', function() {
    $instance = Price::ofMinor(500, 'EUR')->setUnits(3)->setVat(10);

    expect($instance->vat()->getAmount()->compareTo(Money::ofMinor(150, 'EUR')->getAmount()))->toBe(0);
    // Passing "true" to vat->getAmount() should return the VAT value per unit
    expect($instance->vat()->getAmount(true)->compareTo(Money::ofMinor(50, 'EUR')->getAmount()))->toBe(0);
});

it('returns exclusive amount without VAT for all units', function() {
    $instance = Price::ofMinor(500, 'EUR')->setUnits(3);

    expect($instance->exclusive()->getAmount()->compareTo(Money::ofMinor(1500, 'EUR')->getAmount()))->toBe(0);
    // Passing "true" to exclusive() should return the price per unit
    expect($instance->exclusive(true)->getAmount()->compareTo(Money::ofMinor(500, 'EUR')->getAmount()))->toBe(0);

    $instance->setVat(10);

    expect($instance->exclusive()->getAmount()->compareTo(Money::ofMinor(1500, 'EUR')->getAmount()))->toBe(0);
    // Passing "true" to exclusive() should return the price per unit
    expect($instance->exclusive(true)->getAmount()->compareTo(Money::ofMinor(500, 'EUR')->getAmount()))->toBe(0);
});

it('returns inclusive amount with VAT when defined', function() {
    $instance = Price::ofMinor(500, 'EUR')->setUnits(3);

    expect($instance->inclusive()->getAmount()->compareTo(Money::ofMinor(1500, 'EUR')->getAmount()))->toBe(0);
    // Passing "true" to inclusive() should return the price per unit
    expect($instance->inclusive(true)->getAmount()->compareTo(Money::ofMinor(500, 'EUR')->getAmount()))->toBe(0);

    $instance->setVat(10);

    expect($instance->inclusive()->getAmount()->compareTo(Money::ofMinor(1650, 'EUR')->getAmount()))->toBe(0);
    // Passing "true" to inclusive() should return the price per unit
    expect($instance->inclusive(true)->getAmount()->compareTo(Money::ofMinor(550, 'EUR')->getAmount()))->toBe(0);
});


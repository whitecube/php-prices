<?php

namespace Tests\Unit;

use Brick\Money\Money;
use Whitecube\Price\Vat;
use Whitecube\Price\Price;

it('sets VAT from relative percentage', function() {
    $instance = Price::ofMinor(200, 'EUR');

    expect($instance->setVat(22.5))->toBeInstanceOf(Price::class);
    expect($instance->vat()->percentage())->toBe(22.5);
    expect($instance->vat()->money()->__toString())->toBe('EUR 0.45');

    expect($instance->setVat(10))->toBeInstanceOf(Price::class);
    expect($instance->vat()->percentage())->toBe(10.0);
    expect($instance->vat()->money()->__toString())->toBe('EUR 0.20');

    expect($instance->setVat('32,00 %'))->toBeInstanceOf(Price::class);
    expect($instance->vat()->percentage())->toBe(32.0);
    expect($instance->vat()->money()->__toString())->toBe('EUR 0.64');
});

it('unsets VAT when given null', function() {
    $instance = Price::ofMinor(200, 'EUR')->setVat(6);

    expect($instance->setVat(null))->toBeInstanceOf(Price::class);
    expect($instance->vat())->toBeInstanceOf(Vat::class);
    expect($instance->vat()->percentage())->toBe(0.0);
    expect($instance->vat()->money()->__toString())->toBe('EUR 0.00');
    expect($instance->vat(true))->toBeNull();
});

it('returns VAT for all units by default', function() {
    $instance = Price::ofMinor(500, 'EUR')->setUnits(3)->setVat(10);

    expect($instance->vat()->money()->__toString())->toBe('EUR 1.50');
    // Passing "true" to vat->money() should return the VAT value per unit
    expect($instance->vat()->money(true)->__toString())->toBe('EUR 0.50');
});

it('returns exclusive amount without VAT for all units', function() {
    $instance = Price::ofMinor(500, 'EUR')->setUnits(3);

    expect($instance->exclusive()->__toString())->toBe('EUR 15.00');
    // Passing "true" to exclusive() should return the price per unit
    expect($instance->exclusive(true)->__toString())->toBe('EUR 5.00');

    $instance->setVat(10);

    expect($instance->exclusive()->__toString())->toBe('EUR 15.00');
    // Passing "true" to exclusive() should return the price per unit
    expect($instance->exclusive(true)->__toString())->toBe('EUR 5.00');
});

it('returns inclusive amount with VAT when defined', function() {
    $instance = Price::ofMinor(500, 'EUR')->setUnits(3);

    expect($instance->inclusive()->__toString())->toBe('EUR 15.00');
    // Passing "true" to inclusive() should return the price per unit
    expect($instance->inclusive(true)->__toString())->toBe('EUR 5.00');

    $instance->setVat(10);

    expect($instance->inclusive()->__toString())->toBe('EUR 16.50');
    // Passing "true" to inclusive() should return the price per unit
    expect($instance->inclusive(true)->__toString())->toBe('EUR 5.50');
});


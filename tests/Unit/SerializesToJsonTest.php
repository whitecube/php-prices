<?php

namespace Tests\Unit;

use Brick\Money\Money;
use Whitecube\Price\Price;

it('serializes basic price data', function() {
    $price = Price::ofMinor(500, 'EUR')
        ->setUnits(3)
        ->setVat(10.75);

    $json = json_encode($price);

    $data = json_decode($json, true);

    expect(is_array($data))->toBeTrue();
    expect($data['base'] ?? null)->toBe('500');
    expect($data['currency'] ?? null)->toBe('EUR');
    expect($data['units'] ?? null)->toBe(3);
    expect($data['vat'] ?? null)->toBe(10.75);
    expect(is_array($data['total'] ?? null))->toBeTrue();
    expect($data['total']['exclusive'] ?? null)->toBe('1500');
    expect($data['total']['inclusive'] ?? null)->toBe('1661');
});

it('hydrates instance from JSON string', function() {
    $price = Price::ofMinor(500, 'EUR')
        ->setUnits(3)
        ->setVat(10.75);

    $instance = Price::json(json_encode($price));

    expect($instance)->toBeInstanceOf(Price::class);
    expect($instance->getAmount()->compareTo($price->base()->getAmount()))->toBe(0);
    expect($instance->units())->toBe(floatval(3));
    expect($instance->vat()->percentage())->toBe(10.75);
});

it('hydrates instance from JSON array', function() {
    $data = [
        'base' => '500',
        'currency' => 'EUR',
        'units' => 3,
        'vat' => 10.75,
        'total' => [
            'exclusive' => '1500',
            'inclusive' => '1661',
        ],
    ];

    $instance = Price::json($data);

    expect($instance)->toBeInstanceOf(Price::class);
    expect($instance->getAmount()->compareTo(Money::of(5,'EUR')->getAmount()))->toBe(0);
    expect($instance->units())->toBe(floatval(3));
    expect($instance->vat()->percentage())->toBe(10.75);
});

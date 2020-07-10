<?php

namespace Tests\Unit;

use Money\Money;
use Whitecube\Price\Price;

it('serializes basic price data', function() {
    $price = Price::EUR(500)
        ->setUnits(3)
        ->setVat(10.75);

    $json = json_encode($price);

    $data = json_decode($json, true);

    assertTrue(is_array($data));
    assertEquals(500, $data['base'] ?? null);
    assertEquals('EUR', $data['currency'] ?? null);
    assertEquals(3, $data['units'] ?? null);
    assertEquals(10.75, $data['vat'] ?? null);
    assertTrue(is_array($data['total'] ?? null));
    assertEquals(1500, $data['total']['exclusive'] ?? null);
    assertEquals(1661, $data['total']['inclusive'] ?? null);
});

it('hydrates instance from JSON string', function() {
    $price = Price::EUR(500, 3)->setVat(10.75);

    $instance = Price::json(json_encode($price));

    assertInstanceOf(Price::class, $instance);
    assertTrue(Money::EUR(500)->equals($instance->base()));
    assertEquals(3, $instance->units());
    assertEquals(10.75, $instance->vatPercentage());
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

    assertInstanceOf(Price::class, $instance);
    assertTrue(Money::EUR(500)->equals($instance->base()));
    assertEquals(3, $instance->units());
    assertEquals(10.75, $instance->vatPercentage());
});

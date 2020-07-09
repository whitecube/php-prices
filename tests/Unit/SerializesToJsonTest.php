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

    $this->assertTrue(is_array($data));
    $this->assertEquals(500, $data['base'] ?? null);
    $this->assertEquals('EUR', $data['currency'] ?? null);
    $this->assertEquals(3, $data['units'] ?? null);
    $this->assertEquals(10.75, $data['vat'] ?? null);
    $this->assertTrue(is_array($data['total'] ?? null));
    $this->assertEquals(1500, $data['total']['exclusive'] ?? null);
    $this->assertEquals(1661, $data['total']['inclusive'] ?? null);
});

it('hydrates instance from JSON string', function() {
    $price = Price::EUR(500, 3)->setVat(10.75);

    $instance = Price::json(json_encode($price));

    $this->assertInstanceOf(Price::class, $instance);
    $this->assertTrue(Money::EUR(500)->equals($instance->base()));
    $this->assertEquals(3, $instance->units());
    $this->assertEquals(10.75, $instance->vatPercentage());
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

    $this->assertInstanceOf(Price::class, $instance);
    $this->assertTrue(Money::EUR(500)->equals($instance->base()));
    $this->assertEquals(3, $instance->units());
    $this->assertEquals(10.75, $instance->vatPercentage());
});

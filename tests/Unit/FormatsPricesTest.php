<?php

namespace Tests\Unit;

use Brick\Money\Money;
use Whitecube\Price\Price;

it('formats Price instances as inclusive localized strings using application locale', function() {
    setlocale(LC_ALL, 'en_US');

    $price = Price::USD(65550, 8)->setVat(21);

    expect(Price::format($price))->toBe('$6,345.24');
});

it('formats Brick\\Money instances as localized strings using application locale', function() {
    setlocale(LC_ALL, 'en_US');

    $price = Price::USD(65550, 8)->setVat(21);

    expect(Price::format($price->exclusive()))->toBe('$5,244.00');
});

it('formats Vat instances as localized strings using application locale', function() {
    setlocale(LC_ALL, 'en_US');

    $price = Price::USD(65550, 8)->setVat(21);

    expect(Price::format($price->vat()))->toBe('$1,101.24');
});

it('formats monetary values using provided locale', function() {
    setlocale(LC_ALL, 'en_US');

    $price = Price::EUR(65550, 8)->setVat(21);

    expect(Price::format($price, 'de_DE'))->toBe('6.345,24 €');
    expect(Price::format($price->exclusive(), 'fr_BE'))->toBe('5 244,00 €');
    expect(Price::format($price->vat(), 'en_GB'))->toBe('€1,101.24');
});

it('formats monetary values using a previously defined custom formatted closure', function() {
    setlocale(LC_ALL, 'en_US');

    Price::formatUsing(fn($price, $locale = null) => $price->exclusive()->getMinorAmount()->toInt());

    $price = Price::EUR(600, 8)->setVat(21);

    expect(Price::format($price))->toBe('4800');
});

it('formats monetary values using the default formatter despite of the previously defined custom formatted closure', function() {
    setlocale(LC_ALL, 'en_US');
    
    Price::formatUsing(fn($price, $locale = null) => $price->exclusive()->getMinorAmount()->toInt());

    $price = Price::EUR(600, 8)->setVat(21);

    expect(Price::formatDefault($price))->toBe('$58.08');
});

it('formats monetary values using one of the previously defined custom named formatted closures', function() {
    setlocale(LC_ALL, 'nl_BE');

    Price::formatUsing(fn($price, $locale = null) => $price->exclusive()->getMinorAmount()->toInt())
        ->name('rawExclusiveCents');

    Price::formatUsing(fn($price, $locale = null) => Price::formatDefault($price->inclusive()->multipliedBy(-1), $locale))
        ->name('inverted');

    $price = Price::EUR(600, 8)->setVat(21);

    expect(Price::formatRawExclusiveCents($price))->toBe(4800);
    expect(Price::formatInverted($price))->toBe('-58,08 €');
    expect(Price::format($price))->toBe('58,08 €');
});

it('formats monetary values using forwarded method parameters on a previously defined custom formatted closure', function() {
    setlocale(LC_ALL, 'fr_BE');

    Price::formatUsing(function($price, $space = '&nbsp;', $locale = null) {
        return str_replace(' ', $space, Price::format($price, $locale));
    })->name('unbreakable');

    $price = Price::EUR(100000, 2)->setVat(21);

    expect(Price::formatUnbreakable($price))->toBe('2_420,00_€');
});

<?php

namespace Tests\Unit;

use NumberFormatter;
use Brick\Money\Money;
use Whitecube\Price\Price;

it('formats Price instances as inclusive localized strings using application locale', function() {
    setlocale(LC_ALL, 'en_US');

    $price = Price::USD(65550, 8)->setVat(21);

    $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
    expect(Price::format($price))->toBe($fmt->formatCurrency(6345.24, 'USD'));
});

it('formats Brick\\Money instances as localized strings using application locale', function() {
    setlocale(LC_ALL, 'en_US');

    $price = Price::USD(65550, 8)->setVat(21);

    $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
    expect(Price::format($price->exclusive()))->toBe($fmt->formatCurrency(5244.0, 'USD'));
});

it('formats Vat instances as localized strings using application locale', function() {
    setlocale(LC_ALL, 'en_US');

    $price = Price::USD(65550, 8)->setVat(21);

    $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
    expect(Price::format($price->vat()))->toBe($fmt->formatCurrency(1101.24, 'USD'));
});

it('formats monetary values using provided locale', function() {
    setlocale(LC_ALL, 'en_US');

    $price = Price::EUR(65550, 8)->setVat(21);

    $fmt = new NumberFormatter('de_DE', NumberFormatter::CURRENCY);
    expect(Price::format($price, 'de_DE'))->toBe($fmt->formatCurrency(6345.24, 'EUR'));
    $fmt = new NumberFormatter('fr_BE', NumberFormatter::CURRENCY);
    expect(Price::format($price->exclusive(), 'fr_BE'))->toBe($fmt->formatCurrency(5244.0, 'EUR'));
    $fmt = new NumberFormatter('en_GB', NumberFormatter::CURRENCY);
    expect(Price::format($price->vat(), 'en_GB'))->toBe($fmt->formatCurrency(1101.24, 'EUR'));
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

    $price = Price::USD(600, 8)->setVat(21);

    $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
    expect(Price::formatDefault($price))->toBe($fmt->formatCurrency(58.08, 'USD'));
});

it('formats monetary values using one of the previously defined custom named formatted closures', function() {
    setlocale(LC_ALL, 'en_US');

    Price::formatUsing(fn($price, $locale = null) => $price->exclusive()->getMinorAmount()->toInt())
        ->name('rawExclusiveCents');

    Price::formatUsing(fn($price, $locale = null) => Price::formatDefault($price->inclusive()->multipliedBy(-1), $locale))
        ->name('inverted');

    $price = Price::EUR(600, 8)->setVat(21);

    expect(Price::formatRawExclusiveCents($price))->toBe('4800');
    $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
    expect(Price::formatInverted($price))->toBe($fmt->formatCurrency(-58.08, 'EUR'));
    expect(Price::format($price))->toBe($fmt->formatCurrency(58.08, 'EUR'));
});

it('formats monetary values using forwarded method parameters on a previously defined custom formatted closure', function() {
    setlocale(LC_ALL, 'en_US');

    Price::formatUsing(function($price, $max, $locale = null) {
        return ($price->compareTo($max) > 0)
            ? Price::format($max, $locale)
            : Price::format($price, $locale);
    })->name('max');

    $price = Price::EUR(100000, 2)->setVat(21);

    $fmt = new NumberFormatter('fr_BE', NumberFormatter::CURRENCY);
    expect(Price::formatMax($price, Money::ofMinor(180000, 'EUR'), 'fr_BE'))->toBe($fmt->formatCurrency(1800.0, 'EUR'));
});

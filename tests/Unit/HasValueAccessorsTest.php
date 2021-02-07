<?php

namespace Tests\Unit;

use Brick\Money\Money;
use Whitecube\Price\Price;

// TODO : Define what to do with these tests

// it('can access the base amount', function() {
//     $instance = Price::EUR(500, 5);

//     assertEquals('500', $instance->baseAmount());
//     assertEquals('2500', $instance->baseAmount(false));
// });

// it('can access the exclusive amount', function() {
//     $instance = Price::EUR(600, 5)
//         ->addDiscount(function(Money $value) {
//             return $value->minus(Money::EUR(100));
//         })
//         ->setVat(10);

//     assertEquals('500', $instance->exclusiveAmount());
//     assertEquals('2500', $instance->exclusiveAmount(false));

//     assertEquals('500', $instance->amount());
//     assertEquals('2500', $instance->amount(false));
// });

// it('can access the inclusive amount', function() {
//     $instance = Price::EUR(600, 5)
//         ->addDiscount(function(Money $value) {
//             return $value->minus(Money::EUR(100));
//         }, 'foo', true)
//         ->setVat(10);

//     assertEquals('550', $instance->inclusiveAmount());
//     assertEquals('2750', $instance->inclusiveAmount(false));
// });

// it('can access the currency object', function() {
//     $instance = Price::EUR(100);

//     $currency = $instance->currency();

//     assertInstanceOf(Currency::class, $currency);
//     assertTrue($currency->equals(new Currency('EUR')));
// });
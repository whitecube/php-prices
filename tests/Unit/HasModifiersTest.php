<?php

namespace Tests\Unit;

use Brick\Money\Money;
use Whitecube\Price\Price;
use Whitecube\Price\Modifier;
use Tests\Fixtures\AmendableModifier;
use Tests\Fixtures\NonAmendableModifier;
use Tests\Fixtures\CustomAmendableModifier;
use Tests\Fixtures\AfterVatAmendableModifier;

it('can set modifier type', function() {
    $modifier = new Modifier;

    expect($modifier->type())->toBe(Modifier::TYPE_UNDEFINED);
    expect($modifier->setType('bar'))->toBe($modifier);
    expect($modifier->type())->toBe('bar');
});

it('can set modifier key', function() {
    $modifier = new Modifier;

    expect($modifier->key())->toBeNull();
    expect($modifier->setKey('foo-bar'))->toBe($modifier);
    expect($modifier->key())->toBe('foo-bar');
});

it('can set modifier attributes', function() {
    $modifier = new Modifier;

    expect($modifier->attributes())->toBeNull();
    expect($modifier->setAttributes([]))->toBe($modifier);
    expect($modifier->attributes())->toBeNull();
    expect($modifier->setAttributes(['test' => 'one']))->toBe($modifier);
    expect($modifier->attributes())->toBe(['test' => 'one']);
});

it('can set modifier post-VAT application', function() {
    $modifier = new Modifier;

    expect($modifier->appliesAfterVat())->toBeFalse();
    expect($modifier->setPostVat())->toBe($modifier);
    expect($modifier->appliesAfterVat())->toBeTrue();
    expect($modifier->setPostVat(false))->toBe($modifier);
    expect($modifier->appliesAfterVat())->toBeFalse();
});

it('can set modifier per-unit application', function() {
    $modifier = new Modifier;

    expect($modifier->appliesPerUnit())->toBeTrue();
    expect($modifier->setPerUnit(false))->toBe($modifier);
    expect($modifier->appliesPerUnit())->toBeFalse();
    expect($modifier->setPerUnit())->toBe($modifier);
    expect($modifier->appliesPerUnit())->toBeTrue();
});

it('can perfom modifier additions', function() {
    $modifier = new Modifier;

    expect($modifier->add(100))->toBe($modifier);

    $multiple = Price::EUR(500, 2)->addModifier('custom', $modifier);

    expect($multiple->exclusive()->__toString())->toBe('EUR 12.00');

    $modifier->setPerUnit(false);

    $general = Price::EUR(500, 2)->addModifier('custom', $modifier);

    expect($general->exclusive()->__toString())->toBe('EUR 11.00');
});

it('can perfom modifier subtractions', function() {
    $modifier = new Modifier;

    expect($modifier->subtract(100))->toBe($modifier);

    $multiple = Price::EUR(500, 2)->addModifier('custom', $modifier);

    expect($multiple->exclusive()->__toString())->toBe('EUR 8.00');

    $modifier->setPerUnit(false);

    $general = Price::EUR(500, 2)->addModifier('custom', $modifier);

    expect($general->exclusive()->__toString())->toBe('EUR 9.00');
});

it('can perfom modifier multiplications', function() {
    $modifier = new Modifier;

    expect($modifier->multiply(10))->toBe($modifier);

    $price = Price::EUR(500, 2)->addModifier('custom', $modifier);

    expect($price->exclusive()->__toString())->toBe('EUR 100.00');
});

it('can perfom modifier divisions', function() {
    $modifier = new Modifier;

    expect($modifier->divide(10))->toBe($modifier);

    $price = Price::EUR(500, 2)->addModifier('custom', $modifier);

    expect($price->exclusive()->__toString())->toBe('EUR 1.00');
});

it('can perform modifier absolute value', function() {
    $modifier = new Modifier;

    expect($modifier->abs())->toBe($modifier);

    $price = Price::EUR(-500, 2)->addModifier('custom', $modifier);

    expect($price->exclusive()->__toString())->toBe('EUR 10.00');
});

it('cannot add a NULL modifier', function() {
    Price::EUR(500, 2)->addModifier('custom', null);
})->throws(\TypeError::class);

it('cannot add an invalid modifier', function() {
    Price::EUR(500, 2)->addModifier('custom', ['something','unusable']);
})->throws(\TypeError::class);

it('cannot add a non-PriceAmendable modifier instance', function() {
    Price::EUR(500, 2)->addModifier('custom', new NonAmendableModifier());
})->throws(\TypeError::class);

it('can add a numeric modifier', function() {
    $price = Price::EUR(500, 2)->addModifier('custom', '-100');

    expect($price)->toBeInstanceOf(Price::class);
    expect($price->exclusive()->__toString())->toBe('EUR 8.00');
});

it('can add a Money instance modifier', function() {
    $money = Money::of(1, 'EUR');
    $price = Price::EUR(500, 2)->addModifier('custom', $money);

    expect($price)->toBeInstanceOf(Price::class);
    expect($price->exclusive()->__toString())->toBe('EUR 12.00');
});

it('can add a callable modifier', function() {
    $price = Price::EUR(500, 2)->addModifier('custom', function($modifier) {
        $modifier->add(100);
    });

    expect($price)->toBeInstanceOf(Price::class);
    expect($price->exclusive()->__toString())->toBe('EUR 12.00');
});

it('can add a modifier instance', function() {
    $modifier = (new Modifier)->multiply(2)->subtract(200);

    $price = Price::EUR(500, 3)->addModifier('custom', $modifier);

    expect($price->exclusive()->__toString())->toBe('EUR 24.00');
});

it('can add a custom amendable modifier instance', function() {
    $modifier = new AmendableModifier();

    $price = Price::EUR(500, 2)->addModifier('custom', $modifier);

    expect($price->exclusive()->__toString())->toBe('EUR 12.50');
});

it('can add a custom amendable modifier classname', function() {
    $price = Price::EUR(500, 2)->addModifier('custom', AmendableModifier::class);

    expect($price->exclusive()->__toString())->toBe('EUR 12.50');
});

it('can add a custom amendable modifier classname with custom arguments', function() {
    $tax = Money::ofMinor(250, 'EUR');
    $price = Price::EUR(500, 2)->addModifier('custom', CustomAmendableModifier::class, $tax);

    expect($price->exclusive()->__toString())->toBe('EUR 15.00');
});

it('can add a tax modifier', function() {
    $price = Price::EUR(500, 2)->addTax(100);

    expect($price)->toBeInstanceOf(Price::class);

    $modifier = $price->getVatModifiers(false)[0];

    expect($modifier->type())->toBe(Modifier::TYPE_TAX);

    expect($price->exclusive()->__toString())->toBe('EUR 12.00');
});

it('can add a discount modifier', function() {
    $price = Price::EUR(500, 2)->addDiscount(-100);

    expect($price)->toBeInstanceOf(Price::class);

    $modifier = $price->getVatModifiers(false)[0];

    expect($modifier->type())->toBe(Modifier::TYPE_DISCOUNT);

    expect($price->exclusive()->__toString())->toBe('EUR 8.00');
});

it('can apply modifiers before computing VAT', function() {
    $price = Price::EUR(500, 2)
        ->setVat(10)
        ->addModifier('custom', CustomAmendableModifier::class, Money::ofMinor(100, 'EUR'))
        ->addModifier('custom', AmendableModifier::class)
        ->addModifier('custom', AfterVatAmendableModifier::class);

    expect($price->vat()->money()->__toString())->toBe('EUR 1.50');
    expect($price->exclusive()->__toString())->toBe('EUR 15.00');
    expect($price->exclusive(false, true)->__toString())->toBe('EUR 17.00');
    expect($price->inclusive()->__toString())->toBe('EUR 18.50');
});

it('can verify the README intro example', function() {
    $price = Price::EUR(1850);

    expect($price->inclusive()->__toString())->toBe('EUR 18.50');

    $price->setUnits(1.476);

    expect($price->inclusive()->__toString())->toBe('EUR 27.31');

    $price->setVat(6);

    expect($price->inclusive()->__toString())->toBe('EUR 28.95');

    $price->addTax(50);

    expect($price->inclusive()->__toString())->toBe('EUR 29.73');

    $price->addDiscount(-100);

    expect($price->inclusive()->__toString())->toBe('EUR 28.16');
});

it('can return whole modification history', function() {
    $price = Price::EUR(500, 2)
        ->addModifier('custom', AfterVatAmendableModifier::class)
        ->addModifier('something', CustomAmendableModifier::class, Money::ofMinor(100, 'EUR'))
        ->addModifier('custom', AmendableModifier::class);

    $history = $price->modifications();

    expect($history)->toBeArray();
    expect(count($history))->toBe(3);

    expect($history[0]['key'] ?? null)->toBe('bar-foo');
    expect($history[0]['amount']->__toString())->toBe('EUR 2.00');

    expect($history[1]['key'] ?? null)->toBe('foo-bar');
    expect($history[1]['amount']->__toString())->toBe('EUR 3.00');

    expect($history[2]['key'] ?? null)->toBe('after-vat');
    expect($history[2]['amount']->__toString())->toBe('EUR 2.00');
});

it('can return whole modification history with per-unit amounts', function() {
    $price = Price::EUR(500, 2)
        ->addModifier('custom', AfterVatAmendableModifier::class)
        ->addModifier('something', CustomAmendableModifier::class, Money::ofMinor(100, 'EUR'))
        ->addModifier('custom', AmendableModifier::class);

    $history = $price->modifications(true);

    expect($history)->toBeArray();
    expect(count($history))->toBe(3);

    expect($history[0]['key'] ?? null)->toBe('bar-foo');
    expect($history[0]['amount']->__toString())->toBe('EUR 1.00');

    expect($history[1]['key'] ?? null)->toBe('foo-bar');
    expect($history[1]['amount']->__toString())->toBe('EUR 1.50');

    expect($history[2]['key'] ?? null)->toBe('after-vat');
    expect($history[2]['amount']->__toString())->toBe('EUR 1.00');
});

it('can return filtered modification history', function() {
    $price = Price::EUR(500, 2)
        ->addModifier('custom', AfterVatAmendableModifier::class)
        ->addModifier('something', CustomAmendableModifier::class, Money::ofMinor(100, 'EUR'))
        ->addModifier('custom', AmendableModifier::class);

    $history = $price->modifications(false, 'custom');

    expect($history)->toBeArray();
    expect(count($history))->toBe(2);

    expect($history[0]['key'] ?? null)->toBe('foo-bar');
    expect($history[0]['amount']->__toString())->toBe('EUR 3.00');

    expect($history[1]['key'] ?? null)->toBe('after-vat');
    expect($history[1]['amount']->__toString())->toBe('EUR 2.00');
});

it('can return modifications totals', function() {
    $price = Price::EUR(500, 2)
        ->addDiscount(-100)
        ->addDiscount(-50)
        ->addTax(25)
        ->addTax(150)
        ->addModifier('custom', AfterVatAmendableModifier::class)
        ->addModifier('something', CustomAmendableModifier::class, Money::ofMinor(100, 'EUR'))
        ->addModifier('custom', AmendableModifier::class);

    expect($price->discounts()->__toString())->toBe('EUR -3.00');
    expect($price->discounts(true)->__toString())->toBe('EUR -1.50');

    expect($price->taxes()->__toString())->toBe('EUR 3.50');
    expect($price->taxes(true)->__toString())->toBe('EUR 1.75');

    expect($price->modifiers()->__toString())->toBe('EUR 7.63');
    expect($price->modifiers(true)->__toString())->toBe('EUR 3.81');
    expect($price->modifiers(false, 'custom')->__toString())->toBe('EUR 5.13');
});

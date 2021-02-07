# PHP Prices

> 💸 **Version 2.x**
>
> This new major version is shifting the package towards more flexibility and configuration possibilities in general.
>
> One of the main differences is that we replaced [`moneyphp/money`](https://github.com/moneyphp/money) with [`brick/money`](https://github.com/brick/money) under the hood. This introduces a ton of **breaking changes**, mainly on the instantiation methods that now reflect brick/money's API in order to keep things developer friendly. The `1.x` branch will still be available and maintained for a while, but we strongly recommend updating to `2.x`.

Using the underlying [`brick/money`](https://github.com/brick/money) library, this simple Price object allows to work with complex composite monetary values which include exclusive, inclusive, VAT (and other potential taxes) and discount amounts. It makes it safer and easier to compute final displayable prices without having to worry about their construction.

## Install

```
composer require whitecube/php-prices
```

## Getting started

Each `Price` object has a `Brick\Money\Money` instance which is considered to be the item's unchanged, per-unit & exclusive amount. All the composition operations, such as adding VAT or applying discounts, are added on top of this base value.

It is common practice and always best to work with amounts represented in **the smallest currency unit (minor values)** such as "cents".

There are several convenient ways to obtain a `Price` instance :

| Method                                               | Using major values                | Using minor values                  | Defining units                   |
| :--------------------------------------------------- | :-------------------------------- | :---------------------------------- | :------------------------------- |
| [Constructor](#from-constructor)                     | `new Price(Money $base)`          | `new Price(Money $base)`            | `new Price(Money $base, $units)` |
| [Brick/Money methods](#from-brickmoney-like-methods) | `Price::of($major, $currency)`    | `Price::ofMinor($minor, $currency)` | -                                |
| [Currency methods](#from-currency-code-methods)      | -                                 | `Price::EUR($minor)`                | `Price::USD($minor, $units)`     |
| [Parsed strings](#from-parsed-string-values)         | `Price::parse($value, $currency)` | -                                   | -                                |

### From Constructor

You can set this basic value by instantiating the Price directly with the desired `Brick\Money\Money` instance:

```php
use Brick\Money\Money;
use Whitecube\Price\Price;

$base = new Money::ofMinor(500, 'USD');         // $5.00

$single = new Price($base);                     // 1 x $5.00
$multiple = new Price($base, 4);                // 4 x $5.00
```

### From Brick/Money-like methods

For convenience, it is also possible to use the shorthand Money factory methods :

```php
use Whitecube\Price\Price;

$simple = Price::of(5, 'EUR');                  // 1 x €5.00
$minor = Price::ofMinor(500, 'EUR');            // 1 x €5.00
```

Using these static calls, you cannot define quantities or units directly with the constructor methods.

For more information on all the available Brick/Money constructors, please take a look at [their documentation](https://github.com/brick/money).

### From currency-code methods

You can also create an instance directly with the intended currency and quantities using the 3-letter currency ISO codes:

```php
use Whitecube\Price\Price;

$single = Price::EUR(500);                      // 1 x €5.00
$multiple = Price::USD(500, 4);                 // 4 x $5.00
```

Using these static calls, all monetary values are considered minor values (e.g. cents).

For a list of all available ISO 4217 currencies, take a look at [Brick/Money's is-currencies definition](https://github.com/brick/money/blob/master/data/iso-currencies.php).

### From parsed string values

Additionnaly, prices can also be parsed from "raw currency value" strings. This method can be useful but should always be used carefully since it may produce unexpected results in some edge-case situations:

```php
use Whitecube\Price\Price;

$guessCurrency = Price::parse('5,5$');          // 1 x $5.50
$forceCurrency = Price::parse('10', 'EUR');     // 1 x €10.00
```

Using these static calls, you cannot define quantities or units directly with the constructor methods.

Parsing formatted strings is a tricky subject. More information on [parsing string values](#parsing-values) below.

## Accessing the underlying Brick\Money\Money object

Once set, this base value can be accessed using the `base()` method.

```php
$base = $price->base();
```

Getting the `Brick\Money\Currency` instance is just as easy:

```php
$currency = $price->currency();
```

### Modifying the base price

The price object will forward all the `Brick\Money\Money` API method calls to its base value.

> ⚠️ **Warning**: In opposition to [Money](https://github.com/brick/money) objects, Price objects are not immutable. Therefore, operations like plus, minus, etc. will directly modify the price's base value instead of returning a new instance.

```php
use Whitecube\Price\Price;
use Brick\Money\Money;

$price = Price::ofMinor(500, 'USD')->setUnits(2);   // 2 x $5.00

$price->plus(100)                                   // 2 x $6.00
    ->dividedBy(2)                                  // 2 x $3.00
    ->minus(Money::ofMinor(600, 'USD'))             // 2 x $-3.00
    ->abs();                                        // 2 x $3.00

$price->equals(Money::ofMinor(300, 'USD'));         // true
```

Please refer to [`brick/money`'s documentation](https://github.com/brick/money) for the full list of available features.

> 💡 **Nice to know**: Most of the time, you'll be using modifiers to alter a price since its base value is meant to be somehow constant. For more information on modifiers, please take at the ["Adding modifiers" section](#adding-modifiers) below.

## Units & quantities

This package's default behavior is to consider its base price as the "per unit" price. When no units have been specified, it defaults to `1`. You can set the units amount during instantiation:

```php
use Whitecube\Price\Price;
use Brick\Money\Money;

$price = new Price(Money::ofMinor(500, 'EUR'), 2);      // 2 units of €5.00 each
```

Or modify it later using the `setUnits()` method:

```php
$price->setUnits(1.75);                                 // 1.75 x €5.00
```

You can return the units count using the `units()` method:

```php
$quantity = $price->units();                            // 1.75
```

## Adding VAT

VAT can be added in two ways: by providing its relative value (eg. 21%) or by setting its monetary value directly (eg. €2.50).

```php
use Whitecube\Price\Price;

$price = Price::USD(200);                   // 1 x $2.00

$price->setVat(21);                         // VAT is now 21.0%, or $0.42 per unit

$price->setVat(Money::USD(100));            // VAT is now 50.0%, or $1.00 per unit
```

Once set, the price object will be able to provide various VAT-related information:

```php
use Whitecube\Price\Price;

$price = Price::EUR(500, 3)->setVat(10);    // 3 x €5.00

$percentage = $price->vatPercentage();      // 10.0

$vat = $price->vat();                       // €1.50
$excl = $price->exclusive();                // €15.00
$incl = $price->inclusive();                // €16.50

$vatPerUnit = $price->vat(true);            // €0.50
$exclPerUnit = $price->exclusive(true);     // €5.00
$inclPerUnit = $price->inclusive(true);     // €5.50
```

## Adding modifiers

Modifiers are all the custom operations a business needs to apply on a price before displaying it on a bill. They range from discounts to taxes, including custom rules and coupons. These are the main reason this package exists.

### Discounts

```php
use Whitecube\Price\Price;
use Brick\Money\Money;

$price = Price::USD(800, 5)                 // 5 x $8.00
    ->addDiscount(-100)                     // 5 x $7.00
    ->addDiscount(Money::USD(-50));         // 5 x $6.50

// Add discount identifiers if needed:
$price->addDiscount(-50, 'nice-customer');  // 5 x $6.00
```

### Taxes (other than VAT)

```php
use Whitecube\Price\Price;
use Brick\Money\Money;

$price = Price::EUR(125, 10)                // 10 x €1.25
    ->addTax(100)                           // 10 x €2.25                     
    ->addTax(Money::EUR(50));               // 10 x €2.75

// Add tax identifiers if needed:
$price->addTax(50, 'grumpy-customer');      // 10 x €3.25
```

### Custom behavior

Sometimes modifiers cannot be categorized into "discounts" or "taxes", in which case you can add an anonymous "other" modifier type:

```php
use Whitecube\Price\Price;
use Brick\Money\Money;

$price = Price::USD(2000)                   // 1 x $20.00
    ->addModifier(500)                      // 1 x $25.00                
    ->addModifier(Money::USD(-250));        // 1 x $22.50

// Add modifier identifiers if needed:
$price->addModifier(50, 'extra-sauce');     // 1 x $23.00
```

#### Custom modifier types

This package provides an easy way to create your own category of modifiers if you need to differenciate them from classical discounts or taxes.

```php
use Whitecube\Price\Price;

$price = Price::EUR(800, 5)
    ->addModifier(-50, 'my-modifier-key', 'my-modifier-type');
```

> 💡 **Nice to know**: Modifier types (`tax`, `discount`, `other` and your own) are useful for filtering, grouping and displaying sub-totals or price construction details. More information in the ["Displaying modification details" section](#displaying-modification-details) below.

#### Modifier closures

Most of the time, modifiers are more complex to define than simple "+" or "-" operations. Therefore, it is possible to provide your own application logic by passing a closure instead of a monetary value:

```php
use Whitecube\Price\Price;
use Brick\Money\Money;

$price = Price::USD(1250)
    ->addDiscount(function(Money $value) {
        return $value->subtract($value->multiply(0.10));
    })
    ->addTax(function(Money $value) {
        return $value->add($value->multiply(0.27));
    })
    ->addModifier(function(Money $value) {
        return $value->divide(2);
    });
```

#### Modifier classes

For even more flexibility and readability, it is also possible to extract all these features into their own class:

```php
use Whitecube\Price\Price;

$price = Price::EUR(600, 5)
    ->addDiscount(Discounts\FirstOrder::class)
    ->addTax(Taxes\Gambling::class)
    ->addModifier(SomeCustomModifier::class);
```

These classes have to implement the [`Whitecube\Price\PriceAmendable`](https://github.com/whitecube/php-prices/blob/master/src/PriceAmendable.php) interface, which looks more or less like this:

```php
use Brick\Money\Money;
use Whitecube\Price\Modifier;
use Whitecube\Price\PriceAmendable;

class SomeRandomModifier implements PriceAmendable
{
    /**
     * Return the modifier type (tax, discount, other, ...)
     *
     * @return string
     */
    public function type() : string
    {
        return Modifier::TYPE_TAX;
    }

    /**
     * Return the modifier's identification key
     *
     * @return null|string
     */
    public function key() : ?string
    {
        return 'very-random-tax';
    }

    /**
     * Whether the modifier should be applied before the
     * VAT value has been computed.
     *
     * @return bool
     */
    public function isBeforeVat() : bool
    {
        return false;
    }

    /**
     * Apply the modifier on the given Money instance
     *
     * @param \Brick\Money\Money $value
     * @return null|\Brick\Money\Money
     */
    public function apply(Money $value) : ?Money
    {
        if(date('j') > 1) {
            // Do not apply if it's not the first day of the month
            return null;
        }

        // Add 25%
        return $value->multiply(1.25);
    }
}
```

If needed, it is also possible to pass arguments to these custom classes from the Price configuration:

```php
use Brick\Money\Money;
use Whitecube\Price\Price;

$price = Price::EUR(600, 5)
    ->addModifier(BetweenModifier::class, Money::EUR(-100), Money::EUR(100));
```

```php
use Brick\Money\Money;
use Whitecube\Price\PriceAmendable;

class BetweenModifier implements PriceAmendable
{
    public function __construct(Money $minimum, Money $maximum)
    {
        $this->minimum = $minimum;
        $this->maximum = $maximum;
    }

    // ...
}
```

### Before or after VAT?

Depending on the modifier's nature, VAT could be applied before or after its intervention on the final price. All modifiers can be configured to be executed during one of these phases.

When adding discounts, taxes and simple custom modifiers, adding `true` as last argument indicates the modifier should apply before the VAT value is computed:

```php
use Whitecube\Price\Price;

$price = Price::USD(800, 5)                                 // 5 x $8.00
    ->addDiscount(-100, 'discount-key', true)               // 5 x $7.00 -> new VAT base
    ->addTax(50, 'tax-key', true)                           // 5 x $7.50 -> new VAT base
    ->addModifier(100, 'custom-key', 'custom-type', true);  // 5 x $8.50 -> new VAT base
```

In custom classes, this is handled by the `isBeforeVat` method.

> ⚠️ **Warning**: The "isBeforeVAT" argument and methods will **alter the modifiers execution order**. Prices will first apply all the modifiers that should be executed before VAT (in order of appearance), followed by the remaining ones (also in order of appearance).

## Displaying modification details

When debugging or building complex user interfaces, it is often necessary to retrieve the complete Price modification history. This can be done using the `modifications()` method after all the modifiers have been added on the Price instance:

```php
$history = $price->modifications(); // Array containing chronological modifier results
```

It is also possible to filter this history based on the modifier types:

```php
use Whitecube\Price\Modifier;

$history = $price->modifications(Modifier::TYPE_DISCOUNT);  // Only returning discount results
```

## Output

By default, all handled monetary values are wrapped into a `Brick\Money\Money` object. This should be the only way to manipulate these values in order to [avoid decimal approximation errors](https://stackoverflow.com/questions/3730019/why-not-use-double-or-float-to-represent-currency).

### Amounts

Sometimes you'll need to access the `Brick\Money\Money` object's raw value, for example if you wish to store the value in the database. This is possible using on of these shortcut `amount` methods. Please remember that the raw values are treated as strings (instead of integers or floats):

```php
// 5 x €6.00 with 10% VAT and €1.00 discount each (after VAT)
$price = Price::EUR(600, 5)
    ->setVat(10)
    ->addDiscount(function(Money $value) {
        return $value->subtract(Money::EUR(100));
    });

$price->amount();               // '500'
$price->amount(false);          // '2500'
$price->exclusiveAmount();      // Alias of "amount()"
$price->exclusiveAmount(false); // Alias of "amount(false)"
$price->inclusiveAmount();      // '560'
$price->inclusiveAmount(false); // '2800'
$price->baseAmount();           // '600'
$price->baseAmount(false);      // '3000'
```

### JSON

Prices can be serialized to JSON and rehydrated using the `Price::json($value)` method, which can be useful when storing/retrieving prices from a database or an external API for example:

```php
use Whitecube\Price\Price;

$json = json_encode(Price::USD(999, 4)->setVat(6));

$price = Price::json($json);    // 4 x $9.99 with 6% VAT each
```

> 💡 **Nice to know**: you can also use `Price::json()` in order to create a Price object from an associative array, as long as it contains the `base`, `currency`, `units` and `vat` keys.

## Parsing values

There are a few available methods that will allow to transform a monetary string value into a Price object. The generic `parseCurrency` method will try to guess the currency type from the given string:

```php
use Whitecube\Price\Price;

$fromIsoCode = Price::parse('USD 5.50');        // 1 x $5.50
$fromSymbol = Price::parse('10€');              // 1 x €10.00
```

For this to work, the string should **always** contain an indication on the currency being used (either a valid ISO code or symbol). When using symbols, be aware that some of them (`$` for instance) are used in multiple currencies, resulting in ambiguous results.

When you're sure which ISO Currency is concerned, you should directly pass it as the second parameter of the `parse()` method:

```php
use Whitecube\Price\Price;

$priceEUR = Price::parse('5,5 $', 'EUR');       // 1 x €5.50
$priceUSD = Price::parse('0.103', 'USD');       // 1 x $0.10
```

When using dedicated currency parsers, all units/symbols and non-numerical characters are ignored.

---

## 🔥 Sponsorships

If you are reliant on this package in your production applications, consider [sponsoring us](https://github.com/sponsors/whitecube)! It is the best way to help us keep doing what we love to do: making great open source software.

## Contributing

Feel free to suggest changes, ask for new features or fix bugs yourself. We're sure there are still a lot of improvements that could be made, and we would be very happy to merge useful pull requests.

Thanks!

## Made with ❤️ for open source

At [Whitecube](https://www.whitecube.be) we use a lot of open source software as part of our daily work.
So when we have an opportunity to give something back, we're super excited!

We hope you will enjoy this small contribution from us and would love to [hear from you](mailto:hello@whitecube.be) if you find it useful in your projects. Follow us on [Twitter](https://twitter.com/whitecube_be) for more updates!
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

```php
use Whitecube\Price\Price;

$steak = Price::EUR(1850)   // Steak costs €18.50/kg
    ->setUnits(1.476)       // Customer had 1.476kg, excl. total is €27.31
    ->setVat(6)             // There is 6% VAT, incl. total is €28.95
    ->addTax(50)            // There also is a €0.50/kg tax (before VAT), incl. total is €29,74
    ->addDiscount(-100);    // We granted a €1.00/kg discount (before VAT), incl. total is €28,17
```

It is common practice and always best to work with amounts represented in **the smallest currency unit (minor values)** such as "cents".

There are several convenient ways to obtain a `Price` instance :

| Method                                           | Using major values                | Using minor values                  | Defining units                            |
| :----------------------------------------------- | :-------------------------------- | :---------------------------------- | :---------------------------------------- |
| [Constructor](#from-constructor)                 | `new Price(Money $base)`          | `new Price(Money $base)`            | `new Price(Money $base, $units)`          |
| [Brick/Money API](#from-brickmoney-like-methods) | `Price::of($major, $currency)`    | `Price::ofMinor($minor, $currency)` | -                                         |
| [Currency API](#from-currency-code-methods)      | -                                 | `Price::EUR($minor)`                | `Price::USD($minor, $units)`              |
| [Parsed strings](#from-parsed-string-values)     | `Price::parse($value, $currency)` | -                                   | `Price::parse($value, $currency, $units)` |

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

$major = Price::of(5, 'EUR');                   // 1 x €5.00
$minor = Price::ofMinor(500, 'USD');            // 1 x $5.00
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

Additionnaly, prices can also be parsed from "raw currency value" strings. This method can be useful but should always be used carefully since it may produce unexpected results in some edge-case situations, especially when "guessing" the currency :

```php
use Whitecube\Price\Price;

$guessCurrency = Price::parse('5,5$');          // 1 x $5.50
$betterGuess = Price::parse('JMD 5.50');        // 1 x $5.50
$forceCurrency = Price::parse('10', 'EUR');     // 1 x €10.00

$multiple = Price::parse('6.008 EUR', null, 4); // 4 x €6.01
$force = Price::parse('-5 EUR', 'USD', 4);      // 4 x $-5.00
```

Parsing formatted strings is a tricky subject. More information on [parsing string values](#parsing-values) below.

## Accessing the Money objects (getters)

Once set, the **base amount** can be accessed using the `base()` method.

```php
$perUnit = $price->base();                      // Brick\Money\Money
$allUnits = $price->base(false);                // Brick\Money\Money
```

Getting the **currency** instance is just as easy:

```php
$currency = $price->currency();                 // Brick\Money\Currency
```

The **total exclusive amount** (with all modifiers except without VAT):

```php
$perUnit = $price->exclusive(true);             // Brick\Money\Money
$allUnits = $price->exclusive();                // Brick\Money\Money
```

The **total inclusive amount** (with all modifiers and VAT applied):

```php
$perUnit = $price->inclusive(true);             // Brick\Money\Money
$allUnits = $price->inclusive();                // Brick\Money\Money
```

The **VAT** variables:

```php
$vat = $price->vat();                           // Whitecube\Price\Vat
$percentage = $price->vat()->percentage();      // float
$perUnit = $price->vat()->money(true);          // Brick\Money\Money
$allUnits = $price->vat()->money();             // Brick\Money\Money
```

### Comparing amounts

It is possible to check whether a price object's **total inclusive amount** is greater, lesser or equal to another value using the `compareTo` method:

```php
$price = Price::USD(500, 2);                    // 2 x $5.00

$price->compareTo(999);                         // 1
$price->compareTo(1000);                        // 0
$price->compareTo(1001);                        // -1

$price->compareTo(Money::of(10, 'USD'));        // 0
$price->compareTo(Price::USD(250, 4));          // 0
```

For convenience there also is an `equals()` method:

```php
$price->equals(999);                            // false
$price->equals(1000);                           // true
$price->equals(Money::of(10, 'USD'));           // true
$price->equals(Price::USD(250, 4));             // true
```

If you don't want to compare final modified values, there is a `compareBaseTo` method:

```php
$price = Price::USD(500, 2);                    // 2 x $5.00

$price->compareBaseTo(499);                     // 1
$price->compareBaseTo(500);                     // 0
$price->compareBaseTo(501);                     // -1

$price->compareBaseTo(Money::of(5, 'USD'));     // 0
$price->compareBaseTo(Price::USD(500, 4));      // 0
```

## Modifying the base price

The price object will forward all the `Brick\Money\Money` API method calls to its base value.

> ⚠️ **Warning**: In opposition to [Money](https://github.com/brick/money) objects, Price objects are not immutable. Therefore, operations like plus, minus, etc. will directly modify the price's base value instead of returning a new instance.

```php
use Whitecube\Price\Price;
use Brick\Money\Money;

$price = Price::ofMinor(500, 'USD')->setUnits(2);   // 2 x $5.00

$price->minus('2.00')                               // 2 x $3.00
    ->plus('1.50')                                  // 2 x $4.50
    ->dividedBy(2)                                  // 2 x $2.25
    ->multipliedBy(-3)                              // 2 x $-6.75
    ->abs();                                        // 2 x $6.75
```

Please refer to [`brick/money`'s documentation](https://github.com/brick/money) for the full list of available features.

> 💡 **Nice to know**: Most of the time, you'll be using modifiers to alter a price since its base value is meant to be somehow constant. For more information on modifiers, please take at the ["Adding modifiers" section](#adding-modifiers) below.

## Setting units (quantities)

This package's default behavior is to consider its base price as the "per unit" price. When no units have been specified, it defaults to `1`. Since "units" can be anything from a number of undividable products to a measurement, they are always converted to floats.

You can set the units amount (or "quantity" if you prefer) during instantiation:

```php
use Whitecube\Price\Price;
use Brick\Money\Money;

$price = new Price(Money::ofMinor(500, 'EUR'), 2);      // 2 units of €5.00 each
$same = Price::EUR(500, 2);                             // idem
$again = Price::parse('5.00', 'EUR', 2);                // idem
```

...or modify it later using the `setUnits()` method:

```php
$price->setUnits(1.75);                                 // 1.75 x €5.00
```

You can return the units count using the `units()` method (always `float`):

```php
$quantity = $price->units();                            // 1.75
```

## Setting VAT

VAT can be added by providing its relative value (eg. 21%):

```php
use Whitecube\Price\Price;

$price = Price::USD(200);                   // 1 x $2.00

$price->setVat(21);                         // VAT is now 21.0%, or $0.42 per unit

$price->setVat(null);                       // VAT is unset
```

Once set, the price object will be able to provide various VAT-related information:

```php
use Whitecube\Price\Price;

$price = Price::EUR(500, 3)->setVat(10);    // 3 x €5.00

$percentage = $price->vat()->percentage();  // 10.0

$perUnit = $price->vat()->money(true);      // €0.50
$allUnits = $price->vat()->money();         // €1.50
```

## Setting modifiers

Modifiers are all the custom operations a business needs to apply on a price before displaying it on a bill. They range from discounts to taxes, including custom rules and coupons. These are the main reason this package exists.

### Discounts

```php
use Whitecube\Price\Price;
use Brick\Money\Money;

$price = Price::USD(800, 5)                         // 5 x $8.00
    ->addDiscount(-100)                             // 5 x $7.00
    ->addDiscount(Money::of(-5, 'USD'));            // 5 x $6.50
```

### Taxes (other than VAT)

```php
use Whitecube\Price\Price;
use Brick\Money\Money;

$price = Price::EUR(125, 10)                        // 10 x €1.25
    ->addTax(100)                                   // 10 x €2.25                     
    ->addTax(Money::of(0.5, 'EUR'));                // 10 x €2.75
```

### Custom modifier types

Sometimes modifiers cannot be categorized into "discounts" or "taxes", in which case you can add your own modifier type:

```php
use Whitecube\Price\Price;
use Brick\Money\Money;

$price = Price::USD(2000)                           // 1 x $20.00
    ->addModifier('coupon', -500)                   // 1 x $15.00                
    ->addModifier('extra', Money::of(2, 'USD'));    // 1 x $17.00
```

> 💡 **Nice to know**: Modifier types (`tax`, `discount` or your own) are useful for filtering, grouping and displaying sub-totals or price construction details. More information in the ["Displaying modification details" section](#displaying-modification-details) below.

### Complex modifiers

Most of the time, modifiers are more complex to define than simple "+" or "-" operations. Depending on the level of complexity, there are a few options that will let you configure your modifiers just as you wish.

#### Closure modifiers

Instead of providing a monetary value to the modifiers, you can use a closure which will get a `Whitecube\Price\Modifier` instance. This object can then be used to perform some operations on the price value. Available operations are :

| Method       | Description                                                     |
| :----------- | :-------------------------------------------------------------- |
| `add()`      | Registers a `plus()` method call on the `Money` object.         |
| `subtract()` | Registers a `minus()` method call on the `Money` object.        |
| `multiply()` | Registers a `multipliedBy()` method call on the `Money` object. |
| `divide()`   | Registers a `dividedBy()` method call on the `Money` object.    |
| `abs()`      | Registers a `abs()` method call on the `Money` object.          |

All these methods have the same signatures as their `Brick\Money\Money` equivalent. The reason we're not using the same method names is to imply object mutability.

```php
use Whitecube\Price\Price;
use Whitecube\Price\Modifier;

$price = Price::USD(1250)
    ->addDiscount(function(Modifier $discount) {
        $discount->subtract(100)->multiply(0.95);
    })
    ->addTax(function(Modifier $tax) {
        $tax->add(250);
    })
    ->addModifier('lucky', function(Money $modifier) {
        $modifier->divide(2);
    });
```

Furthermore, using closure modifiers you can also add other useful configurations, such as:

| Method                 | Default | Description |
| :--------------------- | :------ | :-----------|
| `setKey(string)`       | `null`  | Define an identifier on the modifier. This can be anything and its main purpose is to make a modifier recognizable on display, for instance a translation key or a CSS class name. |
| `setPostVat(bool)`     | `false` | Indicate whether the modifier should be applied before (`false`) or after (`true`) the VAT has been calculated. More information on this feature [below](#before-or-after-vat). |
| `setPerUnit(bool)`     | `true`  | Indicate whether the `add()` and `subtract()` operations define "per-unit" amounts instead of providing a fixed amount that would be applied no matter the quantity. |
| `setAttributes(array)` | `[]`    | Define as many extra modifier attributes as needed. This can be very usefull in order to display the applied modifiers in complex user interfaces. |

#### Modifier classes

For even more flexibility and readability, it is also possible to extract all these features into their own class:

```php
use Whitecube\Price\Price;

$price = Price::EUR(600, 5)
    ->addDiscount(Discounts\FirstOrder::class)
    ->addTax(Taxes\Gambling::class)
    ->addModifier('custom', SomeCustomModifier::class);
```

These classes have to implement the [`Whitecube\Price\PriceAmendable`](https://github.com/whitecube/php-prices/blob/master/src/PriceAmendable.php) interface, which then looks more or less like this:

```php
use Brick\Money\Money;
use Brick\Math\RoundingMode;
use Whitecube\Price\Modifier;
use Whitecube\Price\PriceAmendable;

class SomeRandomModifier implements PriceAmendable
{
    /**
     * The current modifier "type"
     *
     * @return string
     */
    protected $type;

    /**
     * Return the modifier type (tax, discount, other, ...)
     *
     * @return string
     */
    public function type() : string
    {
        return $this->type;
    }

    /**
     * Define the modifier type (tax, discount, other, ...)
     *
     * @param null|string $type
     * @return $this
     */
    public function setType($type = null)
    {
        $this->type = $type;

        return $this;
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
     * Get the modifier attributes that should be saved in the
     * price modification history.
     *
     * @return null|array
     */
    public function attributes() : ?array
    {
        return [
            'subtitle' => 'Just because we don\'t like you today',
            'color' => 'red',
        ];
    }

    /**
     * Whether the modifier should be applied before the
     * VAT value has been computed.
     *
     * @return bool
     */
    public function appliesAfterVat() : bool
    {
        return false;
    }

    /**
     * Apply the modifier on the given Money instance
     *
     * @param \Brick\Money\Money $build
     * @param float $units
     * @param bool $perUnit
     * @param null|\Brick\Money\Money $exclusive
     * @param null|\Whitecube\Price\Vat $vat
     * @return null|\Brick\Money\Money
     */
    public function apply(Money $build, $units, $perUnit, Money $exclusive = null, Vat $vat = null) : ?Money
    {
        if(date('j') > 1) {
            // Do not apply if it's not the first day of the month
            return null;
        }

        // Otherwise add $2.00 per unit
        $supplement = Money::of(2, 'EUR');
        return $build->plus($perUnit ? $supplement : $supplement->multipliedBy($units, RoundingMode::HALF_UP));
    }
}
```

If needed, it is also possible to pass arguments to these custom classes from the Price configuration:

```php
use Brick\Money\Money;
use Whitecube\Price\Price;

$price = Price::EUR(600, 5)
    ->addModifier('lucky-or-not', BetweenModifier::class, Money::ofMinor(-100, 'EUR'), Money::ofMinor(100, 'EUR'));
```

```php
use Brick\Money\Money;
use Whitecube\Price\PriceAmendable;

class BetweenModifier implements PriceAmendable
{
    protected $minimum;
    protected $maximum;

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

By default modifiers are added before VAT is applied, meaning they will most probably also modify the VAT value. In order to prevent that, it is possible to add a modifier on top of the calculated VAT. Legally speaking this is honestly quite rare but why not:

```php
use Whitecube\Price\Price;

$price = Price::USD(800, 5)->addTax(function($tax) {
    $tax->add(200)->setPostVat();
});
```

In custom classes, this is handled by the `appliesAfterVat` method.

> ⚠️ **Warning**: Applying modifiers after VAT will **alter the modifiers execution order**. Prices will first apply all the modifiers that should be executed before VAT (in order of appearance), then the VAT itself, followed by the remaining modifiers (also in order of appearance).

Inclusive prices will contain all the modifiers (before and after VAT), but exclusive prices only contain the "before VAT" modifiers by default. If you consider the "after VAT" modifiers to be part of the exclusive price, you can always count them in by providing `$includeAfterVat = true` as second argument of the `exclusive()` method :

```php
use Whitecube\Price\Price;

$price = Price::USD(800, 5)->setVat(10)->addTax(function($tax) {
    $tax->add(200)->setPostVat();
});

$price->exclusive();                // $40.00
$price->exclusive(false, true);     // $50.00
$price->inclusive();                // $54.00
```

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
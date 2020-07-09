# PHP Prices

Using the underlying [`moneyphp/money`](https://github.com/moneyphp/money) library, this simple Price object allows to work with complex composite monetary values which include exclusive, inclusive, VAT (or other taxes) and discount amounts. It makes it safer and easier to compute final displayable prices without having to worry about their construction.

## Install

WIP.

## Instantiation

Each `Price` object has a `Money\Money` instance which is considered to be the item's raw, per-unit & exclusive amount. All the composition operations, such as adding VAT or applying a discount, are added on top of this base value.

All amounts are represented in **the smallest currency unit** (eg. cents).

You can set this basic value by instantiating the Price directly with the desired `Money\Money` instance:

```php
use Whitecube\Price\Price;
use Money\Money;
use Money\Currency;

$base = new Money(500, new Currency('USD'));    // $5.00

$single = new Price($base);                     // 1 x $5.00
$multiple = new Price($base, 4);                // 4 x $5.00
```

For convenience, it is also possible to use the shorthand Money factory methods:

```php
use Whitecube\Price\Price;

$single = Price::EUR(500);                      // 1 x €5.00
$multiple = Price::EUR(500, 4);                 // 4 x €5.00
```

For more information on the available currencies and parsable formats, please take a look at [`moneyphp/money`'s documentation](http://moneyphp.org/).

### Accessing the underlying Money/Money object

Once set, this base value can be accessed using the `base()` method.

```php
$base = $price->base();
```

### Modifying the base price

The price object will forward all the `Money\Money` API method calls to its base value.

> ⚠️ **Warning**: In opposition to [Money](https://github.com/moneyphp/money) objects, Price objects are not immutable. Therefore, operations like add, subtract, etc. will directly modify the price's base value instead of returning a new instance.

```php
use Whitecube\Price\Price;
use Money\Money;

$price = Price::USD(500, 2);        // 2 x $5.00

$price->add(Money::USD(100))        // 2 x $6.00
    ->divide(2)                     // 2 x $3.00
    ->subtract(Money::USD(600))     // 2 x $-3.00
    ->absolute();                   // 2 x $3.00

$price->equals(Money::USD(300));    // true
```

Please refer to [`moneyphp/money`'s documentation](http://moneyphp.org/) for the full list of available features.

## Working with units

This package's default behavior is to consider its base price as the "per unit" price. When no units have been specified, it defaults to `1`. You can set the units amount during instantiation:

```php
use Whitecube\Price\Price;

$price = Price::EUR(500, 2);        // 2 units of €5.00 each
```

Or modify it later using the `setUnits()` method:

```php
$price->setUnits(1.75);             // 1.75 x €5.00
```

You can return the units count using the `units()` method:

```php
$units = $price->units();           // 1.75
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

$amount = $price->vat();                    // €1.50
$excl = $price->exclusive();                // €15.00
$incl = $price->inclusive();                // €16.50

$amountPerUnit = $price->vat(true);         // €0.50
$exclPerUnit = $price->exclusive(true);     // €5.00
$inclPerUnit = $price->inclusive(true);     // €5.50
```

## Output

All handled monetary values are always wrapped into a `Money\Money` object. This is and should be the only way to handle these values in order to [avoid decimal approximation errors](https://stackoverflow.com/questions/3730019/why-not-use-double-or-float-to-represent-currency).

### JSON

Prices can be serialized to JSON objects and rehydrated using the `Price::json($value)` method, which can be useful when storing/retrieving prices from a database or an external API for example:

```php
use Whitecube\Price\Price;

$json = json_encode(Price::USD(999, 4)->setVat(6));

$price = Price::json($json);    // 4 x $9.99 with 6% VAT each
```

> 💡 **Nice to know**: you can also use `Price::json()` in order to create a Price object from an associative array, as long as it contains the `base`, `currency`, `units` and `vat` keys.
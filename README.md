# PHP Prices

Using the underlying [`moneyphp/money`](https://github.com/moneyphp/money) library, this simple Price object allows to work with complex composite monetary values which include exclusive, inclusive, VAT (or other taxes) and discount amounts. It makes it safer and easier to compute final displayable prices without having to worry about their construction.

## Install

WIP.

## Documentation

WIP.

### Instantiation

Each `Price` object has a `Money\Money` instance which is considered to be the item's raw, generic & exclusive amount. All the composition operations, such as adding VAT or applying a discount, are added on top of this base value.

All amounts are represented in **the smallest currency unit** (eg. cents).

You can set this basic value by instanciating the Price directly with the desired `Money\Money` instance:

```php
use Whitecube\Price\Price;
use Money\Money;
use Money\Currency;

$base = new Money(500, new Currency('USD'));    // $5.00
$price = new Price($base);
```

For convenience, it is also possible to use the shorthand Money factory methods:

```php
use Whitecube\Price\Price;

$price = Price::EUR(500);   // €5.00
```

For more information on the available currencies and parsable formats, please take a look at [`moneyphp/money`'s documentation](http://moneyphp.org/).

#### Accessing the underlying Money/Money object

Once set, this base value can be accessed using the `base()` method.

```php
$base = $price->base();
```

#### Modifying the base price

The price object will forward all the `Money\Money` API method calls to its base value.

> **Warning**: In opposition to [Money](https://github.com/moneyphp/money) objects, Price objects are not immutable. Therefore, operations like add, subtract, etc. will directly modify the price's base value instead of returning a new instance.

```php
use Whitecube\Price\Price;
use Money\Money;

$price = Price::EUR(500);           // €5.00

$price->add(Money::EUR(100))        // €6.00
    ->divide(2)                     // €3.00
    ->subtract(Money::EUR(600))     // €-3.00
    ->absolute();                   // €3.00

$price->equals(Money::EUR(300));    // true
```

Please refer to [`moneyphp/money`'s documentation](http://moneyphp.org/) for the full list of available features.
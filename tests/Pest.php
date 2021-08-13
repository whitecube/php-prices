<?php

use Whitecube\Price\Price;

uses()->beforeEach(fn() => Price::forgetAllFormatters())->in('Unit');

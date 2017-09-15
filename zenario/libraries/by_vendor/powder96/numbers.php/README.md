# Numbers.php
[Numbers.php](https://github.com/powder96/numbers.php/) - an advanced mathematics toolkit for PHP >= 5.3. It is a port of [Numbers.js](https://github.com/sjkaliski/numbers.js/) - same toolkit for JavaScript.

There is a version of Numbers.php which supports PHP 5.2, but it is no longer developed: https://github.com/powder96/numbers.php/archive/fd946ea8742ba46789dc2a38cc6c1f93a7512e6d.zip

## Description

Numbers.php provides a comprehensive set of mathematical tools that currently are not offered in PHP. These tools include:

* Basic calculations
* Calculus
* Matrix Operations
* Prime Numbers
* Statistics
* More...

A few things to note before using: PHP, like many languages, does not necessarily manage floating points as well as we'd all like it to. For example, if adding decimals, the addition tool won't return the exact value. This is an unfortunate error. Precautions have been made to account for this. After including numbers, you can set an error bound. Anything in this will be considered an "acceptable outcome."

The primary uses cases are calculations and data analysis on the server side. For client side operations, please use [Numbers.js](https://github.com/sjkaliski/numbers.js/).

## How to use

Numbers is pretty straightforward to use.

For example, if we wanted to estimate the integral of sin(x) from -2 to 4, we could:

Use riemann integrals (with 200 subdivisions)
```php
use NumbersPHP\Calculus;
use NumbersPHP\Matrix;
use NumbersPHP\Statistic;
use NumbersPHP\Prime;

Calculus::riemann('sin', -2, 4, 200);
```

Or use adaptive simpson quadrature (with epsilon 0.0001)

```php
Calculus::adaptiveSimpson('sin', -2, 4, 0.0001);
```

User-defined functions can be used too:

```php
function myFunc($x) {
  return 2 * pow($x, 2) + 1;
}
Calculus::riemann('myFunc', -2, 4, 200);

Calculus::adaptiveSimpson(create_function('$x', 'return 2 * pow($x, 2) + 1;'), -2, 4, 0.0001);
```

Now say we wanted to run some matrix calculations:

We can add two matrices

```php
$matrix1 = array(array(0, 1, 2),
				 array(3, 4, 5));
$matrix2 = array(array( 6,  7,  8),
				 array( 9, 10, 11));
Matrix::addition($matrix1, $matrix2);
```

We can transpose a matrix

```php
Matrix::transpose($array);
```

Numbers also includes some basic prime number analysis.  We can check if a number is prime:

```php
//basic check
Prime::simple($number);

// Miller�Rabin primality test
Prime::millerRabin($number);
```

The statistics tools include mean, median, mode, standard deviation, random sample generator, correlation, confidence intervals, t-test, chi-square, and more.

```php
Statistic::mean($array);
Statistic::median($array);
Statistic::mode($array);
Statistic::standardDev($array);
Statistic::randomSample($lower, $upper, $n);
Statistic::correlation($array1, $array2);
```

## Test

Download and install these things:

* [PHP >= 5.3](http://php.net/)
* [Composer PHAR](http://getcomposer.org/composer.phar)
* [PHPUnit PHAR](http://pear.phpunit.de/get/phpunit.phar)

Run in the command prompt:

```cmd
	php composer.phar install
	php phpunit.phar --configuration phpunit.xml.dist
```

If you are going to run tests multiple times and you are using Microsoft(R) Windows(TM), you can use the batch file /test.cmd. Do not forget to set the path to PHP, Composer, and PHPUnit in the beginnig of that file. 

## Authors

### Numbers.js

* Steve Kaliski - [sjkaliski](http://twitter.com/sjkaliski)
* David Byrd - [davidbyrd11](http://twitter.com/davidbyrd11)
* Ethan Resnick - [studip101](http://twitter.com/studip101)
* Ethan - [altercation](https://github.com/altercation)
* Hrishikesh Paranjape - [hrishikeshparanjape](https://github.com/hrishikeshparanjape)
* Greg Leppert - [leppert](https://github.com/leppert)
* Lars-Magnus Skog - [ralphtheninja](https://github.com/ralphtheninja)
* Tim Wood - [codearachnid](https://github.com/codearachnid)
* Miles McCrocklin - [milroc](https://github.com/milroc)
* Nate Kohari - [nkohari](https://github.com/nkohari)
* Eric LaForce - [elaforc](https://github.com/elaforc)
* Kartik Talwar - [KartikTalwar](https://github.com/KartikTalwar)
* [btmills](https://github.com/btmills)
* swair shah - [swairshah](https://github.com/swairshah)
* Jason Hutchinson - [Zikes](https://github.com/Zikes)
* Philip I. Thomas - [philipithomas](https://github.com/philipithomas)
* Brandon Benvie - [Benvie](https://github.com/Benvie)
* Larry Battle - [LarryBattle](https://github.com/LarryBattle)
* [kmcgrane](https://github.com/kmcgrane)

### Numbers.php

* [powder96](https://github.com/powder96/)
* [geopal-solutions](https://github.com/geopal-solutions/)
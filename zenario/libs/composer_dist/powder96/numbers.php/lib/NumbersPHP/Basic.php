<?php

namespace NumbersPHP;

/**
 * Numbers.php
 * http://github.com/powder96/numbers.php
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

final class Basic
{
    /**
     * Determine the summation of numbers in a given array.
     *
     * @param $array array $array of numbers.
     * @return int number sum of numbers in array.
     * @throws \Exception
     */
    public static function sum($array)
    {
        if (!is_array($array)) {
            throw new \Exception('Input must be of type Array');
        }
        $total = 0;
        for ($i = 0, $arrayLength = count($array); $i < $arrayLength; ++$i) {
            if (!is_numeric($array[$i])) {
                throw new \Exception('All elements in array must be numbers');
            }
            $total += $array[$i];
        }
        return $total;
    }

    /**
     * Subtracts elements from one another in array.
     *
     * e.g [5,3,1,-1] -> 5 - 3 - 1 - (-1) = 2
     *
     * @param $array array $array of numbers.
     * @return mixed difference
     * @throws \Exception
     */
    public static function subtraction($array)
    {
        if (!is_array($array)) {
            throw new \Exception('Input must be of type Array');
        }
        if (!is_numeric($array[0])) {
            throw new \Exception('All elements in array must be numbers');
        }
        $total = $array[0];
        for ($i = 1, $arrayLength = count($array); $i < $arrayLength; ++$i) {
            if (!is_numeric($array[$i])) {
                throw new \Exception('All elements in array must be numbers');
            }
            $total -= $array[$i];
        }
        return $total;
    }

    /**
     * Product of all elements in an array.
     *
     * @param array $array of numbers
     * @return int product
     * @throws \Exception
     */
    public static function product($array)
    {
        if (!is_array($array)) {
            throw new \Exception('Input must be of type Array');
        }
        $total = 1;
        for ($i = 0, $arrayLength = count($array); $i < $arrayLength; ++$i) {
            if (!is_numeric($array[$i])) {
                throw new \Exception('All elements in array must be numbers');
            }
            $total *= $array[$i];
        }
        return $total;
    }

    /**
     * Return the square of any value.
     *
     * @param $number number
     * @return mixed square of number
     */
    public static function square($number)
    {
        return $number * $number;
    }

    /**
     * Calculate the binomial coefficient (n choose k)
     *
     * @param number $n    available choices
     * @param number $k    number chosen
     * @return int  number of possible choices
     */
    public static function binomial($n, $k)
    {
        $array = array();
        return self::binomialRecursive($array, $n, $k);
    }

    private static function binomialRecursive(&$array, $n, $k)
    {
        if ($n >= 0 && $k == 0) {
            return 1;
        }
        if ($n == 0 && $k > 0) {
            return 0;
        }
        if (isset($array[$n]) && isset($array[$n][$k]) && $array[$n][$k] > 0) {
            return $array[$n][$k];
        }
        if (!isset($array[$n])) {
            $array[$n] = array();
        }
        $left = self::binomialRecursive($array, $n - 1, $k - 1);
        $right = self::binomialRecursive($array, $n - 1, $k);
        return $array[$n][$k] = $left + $right;
    }

    /**
     * Factorial for some integer.
     *
     * @param $number integer
     * @return int result
     */
    public static function factorial($number)
    {
        $factorial = 1;
        for ($i = 2; $i <= $number; ++$i) {
            $factorial *= $i;
        }
        return $factorial;
    }

    /**
     * Calculate the greastest common divisor amongst two integers.
     * Taken from Ratio.js https://github.com/LarryBattle/Ratio.js
     *
     * @param $a number A.
     * @param $b  number B.
     * @return number greatest common divisor for integers A, B.
     */
    public static function gcd($a, $b)
    {
        $b = (+$b && +$a) ? +$b : 0;
        $a = $b ? $a : 1;
        while ($b) {
            $c = $a % $b;
            $a = $b;
            $b = $c;
        }
        return abs($a);
    }

    /**
     * Calculate the least common multiple amongst two integers.
     *
     * @param $a number A.
     * @param $b number B.
     * @return float least common multiple for integers A, B.
     */
    public static function lcm($a, $b)
    {
        return abs($a * $b) / self::gcd($a, $b);
    }

    /**
     * Retrieve a specified quantity of elements from an array, at random.
     *
     * @param array $array set of values to select from.
     * @param number $quantity quantity of elements to retrieve.
     * @param boolean $allowDuplicates allow the same number to be returned twice.
     * @return array random elements.
     * @throws \Exception
     */
    public static function random($array, $quantity, $allowDuplicates)
    {
        if (empty($array)) {
            throw new \Exception('Empty array');
        }
        $arrayLength = count($array);
        if ($allowDuplicates) {
            $result = array();
            for ($i = 0; $i < $quantity; ++$i) {
                $result[$i] = $array[mt_rand(0, $arrayLength - 1)];
            }
            return $result;
        } else {
            if ($quantity > $arrayLength) {
                throw new \Exception('Quantity requested exceeds size of array');
            }
            return array_slice(self::shuffle($array), 0, $quantity);
        }
    }

    /**
     * Shuffle an array, in place.
     *
     * @param array array to be shuffled.
     * @return array shuffled array.
     */
    public static function shuffle($array)
    {
        shuffle($array);
        return $array;
    }

    /**
     * Find maximum value in an array.
     *
     * @param array array to be traversed.
     * @return number maximum value in the array.
     */
    public static function max($array)
    {
        return max($array);
    }

    /**
     * Find minimum value in an array.
     *
     * @param array array to be traversed.
     * @return number minimum value in the array.
     */
    public static function min($array)
    {
        return min($array);
    }

    /**
     * Create a range of numbers.
     *
     * @param number $start The start of the range.
     * @param number $stop The end of the range.
     * @param number $step
     * @return array An array containing numbers within the range.
     */
    public static function range($start = 0, $stop = null, $step = 1)
    {
        if ($stop === null) {
            $stop = $start;
        }
        if ($stop < $start) {
            $step = -abs($step);
        }
        $array = array();
        $length = max(ceil(($stop - $start) / $step + 1), 0);
        for ($i = 0; $i < $length; ++$i) {
            $array[$i] = $start;
            $start += $step;
        }
        return $array;
    }

    /**
     * Determine if the number is an integer.
     *
     * @param number $number the number
     * @return boolean true for int, false for not int.
     */
    public static function isInt($number)
    {
        if (is_numeric($number)) {
            return (int)$number == (float)$number;
        } else {
            return false;
        }
    }

    /**
     * Calculate the divisor and modulus of two integers.
     *
     * @param number int a.
     * @param number int b.
     * @return array [div, mod].
     */
    public static function divMod($a, $b)
    {
        if (!self::isInt($a) || !self::isInt($b)) {
            return false;
        }

        return array(floor($a / $b), $a % $b);
    }

    /**
     * Calculate:
     * if b >= 1: a^b mod m.
     * if b = -1: modInverse(a, m).
     * if b < 1: finds a modular rth root of a such that b = 1/r.
     *
     * @param number $a Number a.
     * @param number $b Number b.
     * @param number $m Modulo m.
     * @return number see the above documentation for return values.
     */
    public static function powerMod($a, $b, $m)
    {
        // If b < -1 should be a small number, this method should work for now.
        if ($b < -1) {
            return pow($a, $b) % $m;
        }
        if ($b == 0) {
            return 1 % $m;
        }
        if ($b >= 1) {
            $result = 1;
            while ($b > 0) {
                if ($b % 2 == 1) {
                    $result = fmod($result * $a, $m);
                }
                $a = fmod($a * $a, $m);
                $b = $b >> 1;
            }
            return $result;
        }
        if ($b == -1) {
            return self::modInverse($a, $m);
        }
        if ($b < 1) {
            return self::powerMod($a, pow($b, -1), $m);
        }
    }

    /**
     * Calculate the extended Euclid Algorithm or extended GCD.
     *
     * @param number int a.
     * @param number int b.
     * @return array [a, x, y] a is the GCD. x and y are the values such that ax + by = gcd(a, b) .
     */
    public static function egcd($a, $b)
    {
        $x = (+$b && +$a) ? 1 : 0;
        $y = $b ? 0 : 1;
        $u = $x ? 0 : 1;
        $v = $y ? 0 : 1;
        $b = $x ? +$b : 0;
        $a = $b ? $a : 1;
        while ($b) {
            $divMod = self::divMod($a, $b);
            $m = $x - $u * $divMod[0];
            $n = $y - $v * $divMod[0];
            $a = $b;
            $b = $divMod[1];
            $x = $u;
            $y = $v;
            $u = $m;
            $v = $n;
        }
        return array($a, $x, $y);
    }

    /**
     * Calculate the modular inverse of a number.
     *
     * @param number $a Number a.
     * @param number $m Modulo m.
     * @return number if true, return number, else throw error.
     * @throws \Exception
     */
    public static function modInverse($a, $m)
    {
        $r = self::egcd($a, $m);
        if ($r[0] != 1) {
            throw new \Exception('No modular inverse exists');
        }
        return $r[1] % $m;
    }

    /**
     * Determine is two numbers are equal within a given margin of precision.
     *
     * @param number $first first number.
     * @param number $second second number.
     * @param float $epsilon epsilon.
     * @return bool
     */
    public static function numbersEqual($first, $second, $epsilon = Numbers::EPSILON)
    {
        $delta = $first - $second;
        return $delta < $epsilon && $delta > -$epsilon;
    }

    /**
     * Calculate the falling factorial of a number
     *
     * {@see http://mathworld.wolfram.com/FallingFactorial.html}
     *
     * @param number $n base
     * @param number $k Steps to fall
     * @return int Result
     * @throws \Exception
     */
    public static function fallingFactorial($n, $k)
    {
        $i = ($n - $k + 1);
        $r = 1;
        if ($n < 0) {
            throw new \Exception('n cannot be negative');
        }
        if ($k > $n) {
            return 0;
        }
        while ($i <= $n) {
            $r *= $i++;
        }
        return $r;
    }
}

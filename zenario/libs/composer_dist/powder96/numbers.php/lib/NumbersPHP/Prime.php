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

final class Prime
{
    /**
     * Determine if number is prime.  This is far from high performance.
     *
     * @param number number to evaluate.
     * @return boolean return true if value is prime. false otherwise.
     */
    public static function simple($number)
    {
        if ($number == 1) {
            return false;
        }
        if ($number == 2) {
            return true;
        }
        for ($i = 2, $sqrt = ceil(sqrt($number)); $i <= $sqrt; ++$i) {
            if ($number % $i == 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * Returns the prime factors of a number.
     * More info (http://bateru.com/news/2012/05/code-of-the-day-javascript-prime-factors-of-a-number/)
     * Taken from Ratio.js
     *
     * @param number $number
     * @return array an array of numbers
     * @example prime.factorization(20).join(',') === "2,2,5"
     **/
    public static function factorization($number = 0)
    {
        $number = floor($number);
        $continue = 1 < $number && is_finite($number);
        $factors = array();
        while ($continue) {
            $sqrt = sqrt($number);
            $x = 2;
            if (fmod($number, $x) != 0) {
                $x = 3;
                while ((fmod($number, $x) != 0) && (($x += 2) < $sqrt)) {

                }
            }
            $x = ($sqrt < $x) ? $number : $x;
            $factors[] = $x;
            $continue = $x != $number;
            $number /= $x;
        }
        return $factors;
    }

    /**
     * Determine if a number is prime in Polynomial time, using a randomized algorithm.
     * http://en.wikipedia.org/wiki/Miller-Rabin_primality_test
     *
     * @param number $n number to Evaluate.
     * @param number $k number to Determine accuracy rate (number of trials) default value = 20.
     * @return boolean return true if value is prime. false otherwise.
     */
    public static function millerRabin($n, $k = 20)
    {
        if ($n == 2) {
            return true;
        }
        if (!Basic::isInt($n) || $n <= 1 || $n % 2 == 0) {
            return false;
        }
        $s = 0;
        $d = $n - 1;
        while (true) {
            $divMod = Basic::divMod($d, 2);
            if ($divMod[1] == 1) {
                break;
            }
            $s += 1;
            $d = $divMod[0];
        }
        for ($i = 0; $i < $k; ++$i) {
            if (($n - 2) > 2) {
                $a = floor(mt_rand(2, $n - 2));
            }
            else {
                $a = floor(mt_rand($n - 2, 2));
            }
            if (self::millerRabinTryComposite($a, $s, $d, $n)) {
                return false;
            }
        }
        return true;
    }

    private static function millerRabinTryComposite($a, $s, $d, $n)
    {
        if (Basic::powerMod($a, $d, $n) == 1) {
            return false;
        }
        for ($i = 0; $i < $s; ++$i) {
            if (Basic::powerMod($a, pow(2, $i) * $d, $n) == $n - 1) {
                return false;
            }
        }
        return true;
    }

    /**
     * Return a list of prime numbers from 1...n, inclusive.
     *
     * @param number $limit upper limit of test n.
     * @return array list of values that are prime up to n.
     */
    public static function sieve($limit)
    {
        if ($limit < 2) {
            return array();
        }
        $result = array(2);
        for ($i = 3; $i <= $limit; ++$i) {
            $isPrime = true;
            foreach ($result as $j) {
                if ($i % $j == 0) {
                    $isPrime = false;
                    break;
                }
            }
            if ($isPrime) {
                $result[] = $i;
            }
        }
        return $result;
    }

    /**
     * Determine if two numbers are coprime.
     *
     * @param number number.
     * @param number number.
     * @return boolean whether the values are coprime or not.
     */
    public static function coprime($a, $b)
    {
        return Basic::gcd($a, $b) == 1;
    }

    /**
     * Determine if a number is a perfect power.
     * Please note that this method does not find the minimal value of k where
     * m^k = n
     * http://en.wikipedia.org/wiki/Perfect_power
     *
     * @param number $number value in question
     * @return array|boolean [m, k] if it is a perfect power, false otherwise
     */
    public static function getPerfectPower($number)
    {
        $test = self::getPrimePower($number);
        if ($test !== false && $test[1] > 1) {
            return $test;
        }
        return false;
    }

    /**
     * Determine if a number is a prime power and return the prime and the power.
     * http://en.wikipedia.org/wiki/Prime_power
     *
     * @param number $number value in question
     * @return array|boolean  if it is a prime power, return [prime, power].
     */
    public static function getPrimePower($number)
    {
        if ($number < 2) {
            return false;
        }
        if (self::millerRabin($number)) {
            return array($number, 1);
        }
        if ($number % 2 == 0) {
            return array(2, strlen(base_convert((string)$number, 10, 2)) - 1);
        }
        $factors = self::factorization($number);
        if (empty($factors)) {
            return false;
        }
        for ($i = 0, $factorsLength = count($factors); $i < $factorsLength; ++$i) {
            $t = $p = 0;
            while ($t <= $number) {
                $t = pow($factors[$i], $p);
                if ($t / $number == 1) {
                    return array($factors[$i], $p);
                }
                ++$p;
            }
        }
        return false;
    }
}

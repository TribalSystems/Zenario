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

final class Generators
{
    /**
     * Fast Fibonacci Implementation
     *
     * @param number number to calculate
     * @return number nth fibonacci number
     */
    public static function fibonacci($number)
    {
        // Adapted from
        // http://bosker.wordpress.com/2011/04/29/the-worst-algorithm-in-the-world/
        $bits = array();
        while ($number > 0) {
            $bits[] = ($number < 2) ? $number : $number % 2;
            $number = floor($number / 2);
        }
        $bits = array_reverse($bits);

        $a = 1;
        $b = 0;
        $c = 1;
        foreach ($bits as $bit) {
            if ($bit) {
                list($a, $b) = array(($a + $c) * $b, $b * $b + $c * $c);
            } else {
                list($a, $b) = array($a * $a + $b * $b, ($a + $c) * $b);
            }
            $c = $a + $b;
        }
        return $b;
    }

    /**
     * Populate the given array with a Collatz Sequence.
     *
     * @param number $number first number.
     * @param array $array arrary to be populated.
     * @return array array populated with Collatz sequence
     */
    public static function collatz($number, &$array)
    {
        $array[] = $number;
        if ($number === 1) {
            return $array;
        }
        if ($number % 2 === 0) {
            self::collatz($number / 2, $array);
        } else {
            self::collatz(3 * $number + 1, $array);
        }
    }
}

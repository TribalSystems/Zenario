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

final class DSP
{
    /**
     * Returns an array composed of elements from arr, starting at index start
     * and counting by step.
     *
     * @param array $array Input array.
     * @param number $start Starting array index.
     * @param number $step Step size.
     * @return array Resulting sub-array.
     */
    public static function segment($array, $start, $step)
    {
        $result = array();
        for ($i = $start, $arrayLength = count($array); $i < $arrayLength; $i += $step) {
            $result[] = $array[$i];
        }
        return $result;
    }

    /**
     * Returns an array of complex numbers representing the frequency spectrum
     * of real valued time domain sequence array. (count($array) must be integer power of 2)
     * Inspired by http://rosettacode.org/wiki/Fast_Fourier_transform#Python
     *
     * @param array $array Real-valued series input, eg. time-series.
     * @return array Array of complex numbers representing input signal in Fourier domain.
     * @throws \Exception
     */
    public static function fft($array)
    {
        $arrayLength = count($array);
        if ($arrayLength <= 1) {
            return array(new Complex($array[0], 0));
        }
        if (log($arrayLength) / M_LN2 % 1 !== 0) {
            throw new \Exception('Array length must be integer power of 2');
        }
        $even = self::fft(self::segment($array, 0, 2));
        $odd = self::fft(self::segment($array, 1, 2));
        $result = array();
        $halfLength = $arrayLength / 2;
        for ($k = 0; $k < $arrayLength; ++$k) {
            $phase = -2 * M_PI * $k / $arrayLength;
            $phasor = new Complex(cos($phase), sin($phase));
            if ($k < $halfLength) {
                $result[$k] = $even[$k]->add($phasor->multiply($odd[$k]));
            } else {
                $result[$k] = $even[$k - $halfLength]->subtract($phasor->multiply($odd[$k - $halfLength]));
            }
        }
        return $result;
    }
}

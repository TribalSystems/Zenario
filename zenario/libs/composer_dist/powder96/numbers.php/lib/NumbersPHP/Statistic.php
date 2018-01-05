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

final class Statistic
{
    /**
     * Calculate the mean value of a set of numbers in array.
     *
     * @param array $array set of values.
     * @return number mean value.
     */
    public static function mean($array)
    {
        $length = count($array);
        $sum = Basic::sum($array);
        return $sum / $length;
    }

    /**
     * Calculate the median value of a set of numbers in array.
     *
     * @param array $array set of values.
     * @return number median value.
     */
    public static function median($array)
    {
        return self::quantile($array, 1, 2);
    }

    /**
     * Calculate the mode value of a set of numbers in array.
     *
     * @param array $array set of values.
     * @return number mode value.
     */
    public static function mode($array)
    {
        $counts = array();
        for ($i = 0, $arrayLength = count($array); $i < $arrayLength; ++$i) {
            if (!isset($counts[$array[$i]])) {
                $counts[$array[$i]] = 0;
            } else {
                ++$counts[$array[$i]];
            }
        }
        arsort($counts);
        reset($counts);
        return key($counts);
    }

    /**
     * Calculate the kth q-quantile of a set of numbers in an array.
     * As per http://en.wikipedia.org/wiki/Quantile#Quantiles_of_a_population
     * Ex: Median is 1st 2-quantile
     * Ex: Upper quartile is 3rd 4-quantile
     *
     * @param array $array set of values.
     * @param number $k index of quantile.
     * @param number $q number of quantiles.
     * @return number kth q-quantile of values.
     */
    public static function quantile($array, $k, $q)
    {
        if ($k == 0) {
            return Basic::min($array);
        }
        if ($k == $q) {
            return Basic::max($array);
        }
        sort($array);
        $index = count($array) * $k / $q;
        if (fmod($index, 1) == 0) {
            return 0.5 * $array[$index - 1] + 0.5 * $array[$index];
        }
        return $array[floor($index)];
    }

    /**
     * Return a set of summary statistics provided an array.
     *
     * @param $array
     * @return array summary statistics.
     */
    public static function report($array)
    {
        return array(
            'mean' => self::mean($array),
            'firstQuartile' => self::quantile($array, 1, 4),
            'median' => self::median($array),
            'thirdQuartile' => self::quantile($array, 3, 4),
            'standardDev' => self::standardDev($array)
        );
    }

    /**
     * Return a random sample of values over a set of bounds with
     * a specified quantity.
     *
     * @param number $lower lower bound.
     * @param number $upper upper bound.
     * @param number $quantity quantity of elements in random sample.
     * @return array random sample.
     */
    public static function randomSample($lower, $upper, $quantity)
    {
        $precision = pow(Numbers::EPSILON, -1);
        $sample = array();
        while (count($sample) < $quantity) {
            $sample[] = mt_rand($lower * $precision, $upper * $precision) / $precision;
        }
        return $sample;
    }

    /**
     * Evaluate the standard deviation for a set of values.
     *
     * @param array $array set of values.
     * @return number standard deviation.
     */
    public static function standardDev($array)
    {
        $mean = self::mean($array);
        $squares = array();
        foreach ($array as $element) {
            $squares[] = pow($element - $mean, 2);
        }
        return sqrt(1 / count($array) * Basic::sum($squares));
    }

    /**
     * Evaluate the correlation amongst a set of values.
     *
     * @param array $array1
     * @param array $array2
     * @return float number correlation.
     * @throws \Exception
     */
    public static function correlation($array1, $array2)
    {
        if (count($array1) != count($array2)) {
            throw new \Exception('Array mismatch');
        }
        return self::covariance($array1, $array2) / (self::standardDev($array1) * self::standardDev($array2));
    }

    /**
     * Calculate the Coefficient of Determination of a dataset and regression line.
     *
     * @param array $source Source data.
     * @param array $regression Regression data.
     * @return number A number between 0 and 1.0 that represents how well the regression line fits the data.
     */
    public static function rSquared($source, $regression)
    {
        $residualSumOfSquares = Basic::sum(
            array_map(
                create_function('$source, $regression', 'return pow($source - $regression, 2);'),
                $source,
                $regression
            )
        );
        $totalSumOfSquares = Basic::sum(
            array_map(
                create_function('$source', 'return pow($source - ' . self::mean($source) . ', 2);'),
                $source
            )
        );
        return 1 - ($residualSumOfSquares / $totalSumOfSquares);
    }

    /**
     * Create a function to calculate the exponential regression of a dataset.
     *
     * @param array $arrayY set of values.
     * @return array a function to accept X values and
     * return corresponding regression Y values and a coefficient of determination
     */
    public static function exponentialRegression($arrayY)
    {
        $arrayLength = count($arrayY);
        $arrayX = Basic::range(1, $arrayLength);

        $xSum = Basic::sum($arrayX);
        $yLog = array_map('log', $arrayY);
        $yLogSum = Basic::sum($yLog);
        $xSquaredSum = Basic::sum(array_map('NumbersPHP\Basic::square', $arrayX));
        $xyLogSum = Basic::sum(array_map(create_function('$x, $yLog', 'return $x * $yLog;'), $arrayX, $yLog));

        $a = ($yLogSum * $xSquaredSum - $xSum * $xyLogSum) / ($arrayLength * $xSquaredSum - $xSum * $xSum);
        $b = ($arrayLength * $xyLogSum - $xSum * $yLogSum) / ($arrayLength * $xSquaredSum - $xSum * $xSum);

        $function = create_function(
            '$x',
            'if(is_array($x)) {' .
            'foreach($x as &$value)' .
            '$value = exp(' . $a . ') * exp(' . $b . ' * $value);' .
            'return $x;' .
            '}' .
            'else ' .
            'return exp(' . $a . ') * exp(' . $b . ' * $x);'
        );

        return array($function, self::rSquared($arrayY, array_map($function, $arrayX)));
    }

    /**
     * Create a function to calculate the linear regression of a dataset.
     *
     * @param array $arrayX X array.
     * @param array $arrayY Y array.
     * @return callback A function which given X or array of X values will return Y.
     */
    public static function linearRegression($arrayX, $arrayY)
    {
        $arrayLength = count($arrayY);
        $xSum = Basic::sum($arrayX);
        $ySum = Basic::sum($arrayY);
        $xySum = Basic::sum(array_map(create_function('$x, $y', 'return $x * $y;'), $arrayX, $arrayY));
        $xSquaredSum = Basic::sum(array_map('NumbersPHP\Basic::square', $arrayX));
        $xMean = self::mean($arrayX);
        $yMean = self::mean($arrayY);

        $b = ($xySum - 1 / $arrayLength * $xSum * $ySum) / ($xSquaredSum - 1 / $arrayLength * $xSum * $xSum);
        $a = $yMean - $b * $xMean;

        return create_function(
            '$x',
            'if(is_array($x)) {' .
            'foreach($x as &$value)' .
            '$value = ' . $a . ' + ' . $b . ' * $value;' .
            'return $x;' .
            '}' .
            'else ' .
            'return ' . $a . ' + ' . $b . ' * $x;'
        );
    }

    /**
     * Evaluate the covariance amongst 2 sets.
     *
     * @param array $array1 set 1 of values.
     * @param array $array2 set 2 of values.
     * @return number covariance.
     * @throws \Exception
     */
    public static function covariance($array1, $array2)
    {
        if (count($array1) != count($array2)) {
            throw new \Exception('Array mismatch');
        }
        $arrayLength = count($array1);
        $sum1 = Basic::sum($array1);
        $sum2 = Basic::sum($array2);
        $total = 0;
        for ($i = 0; $i < $arrayLength; ++$i) {
            $total += $array1[$i] * $array2[$i];
        }
        return ($total - $sum1 * $sum2 / $arrayLength) / $arrayLength;
    }
}

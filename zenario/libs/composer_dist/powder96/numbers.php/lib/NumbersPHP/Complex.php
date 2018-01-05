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

final class Complex
{
    /**
     * @var float
     */
    public $real;

    /**
     * @var float
     */
    public $imaginary;

    /**
     * Create a complex number.
     * @param float $real
     * @param float $imaginary
     * @return Complex
     */
    public function __construct($real, $imaginary)
    {
        $this->real = $real;
        $this->imaginary = $imaginary;
    }

    /**
     * Convert complex number to string.
     * @return string
     */
    public function __toString()
    {
        return 'Complex(' . $this->real . ', ' . $this->imaginary . ')';
    }

    /**
     * @return float
     */
    public function getReal()
    {
        return $this->real;
    }

    /**
     * @return float
     */
    public function getImaginary()
    {
        return $this->imaginary;
    }

    /**
     * Add a complex number to this one.
     *
     * @param Complex Number to add.
     * @return Complex New complex number (sum).
     */
    public function add($addend)
    {
        return new Complex($this->real + $addend->real, $this->imaginary + $addend->imaginary);
    }

    /**
     * Subtract a complex number from this one.
     *
     * @param Complex Number to subtract.
     * @return Complex New complex number (difference).
     */
    public function subtract($subtrahend)
    {
        return new Complex($this->real - $subtrahend->real, $this->imaginary - $subtrahend->imaginary);
    }

    /**
     * Multiply a complex number with this one.
     *
     * @param Complex Number to multiply by.
     * @return Complex New complex number (product).
     */
    public function multiply($multiplier)
    {
        return new Complex(
            $this->real * $multiplier->real - $this->imaginary * $multiplier->imaginary,
            $this->imaginary * $multiplier->real + $this->real * $multiplier->imaginary
        );
    }

    /**
     * Divide this number with another complex number.
     *
     * @param Complex $divisor Divisor.
     * @return Complex New complex number (quotient).
     */
    public function divide($divisor)
    {
        $denominator = $divisor->real * $divisor->real + $divisor->imaginary * $divisor->imaginary;
        return new Complex(
            ($this->real * $divisor->real + $this->imaginary * $divisor->imaginary) / $denominator,
            ($this->imaginary * $divisor->real - $this->real * $divisor->imaginary) / $denominator
        );
    }

    /**
     * Get the magnitude of this number.
     *
     * @return number Magnitude.
     */
    public function magnitude()
    {
        return sqrt($this->real * $this->real + $this->imaginary * $this->imaginary);
    }

    /**
     * Get the phase of this number.
     *
     * @return number Phase.
     */
    public function phase()
    {
        return atan2($this->imaginary, $this->real);
    }

    /**
     * Conjugate the imaginary part
     *
     * @return Complex Conjugated number
     */
    public function conjugate()
    {
        return new Complex($this->real, -$this->imaginary);
    }

    /**
     * Raises this complex number to the nth power.
     *
     * @param {number} power to raise this complex number to.
     * @return Complex the nth power of this complex number.
     */
    public function pow($power)
    {
        $c = pow($this->magnitude(), $power);
        return new Complex($c * cos($power * $this->phase()), $c * sin($power * $this->phase()));
    }

    /**
     * Raises this complex number to given complex power.
     *
     * @param Complex $power $power the complex number to raise this complex number to.
     * @return Complex this complex number raised to the given complex number.
     */
    public function complexPow($power)
    {
        $square = $this->real * $this->real + $this->imaginary * $this->imaginary;
        $multiplier = pow($square, $power->real / 2) * pow(M_E, -$power->imaginary * $this->phase());
        $theta = $power->real * $this->phase() + 0.5 * $power->imaginary * log($square);
        return new Complex($multiplier * cos($theta), $multiplier * sin($theta));
    }

    /**
     * Find all the nth roots of this complex number.
     *
     * @param number $root root of this complex number to take.
     * @return array an array of size $root with the roots of this complex number.
     */
    public function roots($root)
    {
        $result = array();
        for ($i = 0; $i < $root; ++$i) {
            $theta = ($this->phase() + 2 * M_PI * $i) / $root;
            $radius = pow($this->magnitude(), 1 / $root);
            $result[$i] = new Complex($radius * cos($theta), $radius * sin($theta));
        }
        return $result;
    }

    /**
     * Returns the sine of this complex number.
     *
     * @return Complex the sine of this complex number.
     */
    public function sin()
    {
        $e = new Complex(M_E, 0);
        $i = new Complex(0, 1);
        $negativeI = new Complex(0, -1);
        $numerator = $e->complexPow($i->multiply($this))->subtract($e->complexPow($negativeI->multiply($this)));
        return $numerator->divide(new Complex(0, 2));
    }

    /**
     * Returns the cosine of this complex number.
     *
     * @return Complex the cosine of this complex number.
     */
    public function cos()
    {
        $e = new Complex(M_E, 0);
        $i = new Complex(0, 1);
        $negativeI = new Complex(0, -1);
        $numerator = $e->complexPow($i->multiply($this))->add($e->complexPow($negativeI->multiply($this)));
        return $numerator->divide(new Complex(2, 0));
    }

    /**
     * Returns the tangent of this complex number.
     *
     * @return Complex the tangent of this complex number.
     */
    public function tan()
    {
        return $this->sin()->divide($this->cos());
    }

    /**
     * Checks for equality between this complex number and another
     * within a given range defined by epsilon.
     *
     * @param Complex $complex complex number to check this number against.
     * @param float $epsilon
     * @return bool true if equal within epsilon, false otherwise
     */
    public function equals($complex, $epsilon = Numbers::EPSILON)
    {
        return Basic::numbersEqual($this->real, $complex->real, $epsilon) &&
            Basic::numbersEqual($this->imaginary, $complex->imaginary, $epsilon);
    }
}

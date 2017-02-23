<?php

/*
	http://stackoverflow.com/questions/14994941/numbers-to-roman-numbers-with-php
*/

function convertToRoman($num){ 
	$n = intval($num); 
	$res = ''; 

	//array of roman numbers
	$romanNumber_Array = array( 
		'M'  => 1000, 
		'CM' => 900, 
		'D'  => 500, 
		'CD' => 400, 
		'C'  => 100, 
		'XC' => 90, 
		'L'  => 50, 
		'XL' => 40, 
		'X'  => 10, 
		'IX' => 9, 
		'V'  => 5, 
		'IV' => 4, 
		'I'  => 1); 

	foreach ($romanNumber_Array as $roman => $number){ 
		//divide to get  matches
		$matches = intval($n / $number); 

		//assign the roman char * $matches
		$res .= str_repeat($roman, $matches); 

		//substract from the number
		$n = $n % $number; 
	} 

	// return the result
	return $res; 
}
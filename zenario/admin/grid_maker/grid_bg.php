<?php

//require '../../adminheader.inc.php';

require '../../visitorheader.inc.php';


$fluid = (int) ze::get('fluid');
//$border = (int) ze::get('border');
$save = (int) ze::get('save');
$cols = (int) ze::get('cols');
$gColWidth = (int) ze::get('gColWidth');
$gGutter = (float) ze::get('gGutter');
$gGutterLeftEdge = (float) ze::get('gGutterLeftEdge');
$gGutterRightEdge = (float) ze::get('gGutterRightEdge');
$minWidth = (int) ze::get('minWidth');
$maxWidth = (int) ze::get('maxWidth');
$minHeight = (int) ze::get('minHeight');
if(!$minHeight) $minHeight = ze::get('png') ? 500 : 50;

if ($fluid) {
	$width = $maxWidth;
} else {
	$width = $cols * $gColWidth + ($cols - 1) * $gGutter + $gGutterLeftEdge + $gGutterRightEdge;
}

if ($cols < 1
 || $width > 4000) {
	exit;
}

function imageWithBars(&$img, $cols, $gColWidth, $gGutter, $gGutterLeftEdge, $r, $g, $b, $a = 0) {
	global $minHeight;
	
	for ($i = 0; $i < $cols; ++$i) {
		$start = $i * $gColWidth + $i * $gGutter + $gGutterLeftEdge;
		imagefilledrectangle($img->getHandle(), round($start), 0, round($start + $gColWidth - 1), $minHeight, $img->getExactColorAlpha($r, $g, $b, $a));
	}
}

$img = WideImage\WideImage::createTrueColorImage($width, $minHeight);
imagefilledrectangle($img->getHandle(), 0, 0, $width, $minHeight, $img->getExactColorAlpha(0x8b, 0xa9, 0xba, 0x40));

if ($fluid) {
	$gColWidthMax = $maxWidth * (100 - ($cols - 1) * $gGutter - $gGutterLeftEdge - $gGutterRightEdge) / $cols / 100;
	$gGutterLeftEdge = $gGutterLeftEdge * $maxWidth / 100;
	$gGutterRightEdge = $gGutterRightEdge * $maxWidth / 100;
	
	imageWithBars($img, $cols, $gColWidthMax, $gGutter * $maxWidth / 100, $gGutterLeftEdge, 0xce, 0xd7, 0xdd, 0x40);
	
} else {
	imageWithBars($img, $cols, $gColWidth, $gGutter, $gGutterLeftEdge, 0xce, 0xd7, 0xdd, 0x40);
}


if ($gGutterLeftEdge > 0) {
	imagefilledrectangle($img->getHandle(), 0, 0, round($gGutterLeftEdge - 1), $minHeight, $img->getExactColorAlpha(0x37, 0x4f, 0x5d, 0x40));
}

if ($gGutterRightEdge > 0) {
	imagefilledrectangle($img->getHandle(), round($width - $gGutterRightEdge), 0, $width - 1, $minHeight, $img->getExactColorAlpha(0x37, 0x4f, 0x5d, 0x40));
}



/*
	
		 * Returns a RGBA array for pixel at $x, $y
		 * 
		 * @param int $x
		 * @param int $y
		 * @return array RGB array 
		
		function getRGBAt($x, $y)
		{
			return $this->getColorRGB($this->getColorAt($x, $y));
		}
		
		 * Writes a pixel at the designated coordinates
		 * 
		 * Takes an associative array of colours and uses getExactColor() to
		 * retrieve the exact index color to write to the image with.
		 *
		 * @param int $x
		 * @param int $y
		 * @param array $color
		function setRGBAt($x, $y, $color)
		{
			$this->setColorAt($x, $y, $this->getExactColor($color));
		}
*/

if ($save) {
	header('Content-Disposition: attachment; filename="'. ($minWidth && $minWidth != $width? $minWidth. '-' : ''). $width. 'grid.png"');
}

$img->output('png');
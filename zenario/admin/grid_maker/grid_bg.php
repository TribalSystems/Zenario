<?php

//require '../../adminheader.inc.php';

require '../../visitorheader.inc.php';
require_once CMS_ROOT. 'zenario/libs/manually_maintained/lgpl/wideimage/WideImage.php';


$fluid = (int) ($_GET['fluid'] ?? false);
//$border = (int) ($_GET['border'] ?? false);
$save = (int) ($_GET['save'] ?? false);
$gCols = (int) ($_GET['gCols'] ?? false);
$gColWidth = (int) ($_GET['gColWidth'] ?? false);
$gGutter = (float) ($_GET['gGutter'] ?? false);
$gGutterLeftEdge = (float) ($_GET['gGutterLeftEdge'] ?? false);
$gGutterRightEdge = (float) ($_GET['gGutterRightEdge'] ?? false);
$minWidth = (int) ($_GET['minWidth'] ?? false);
$maxWidth = (int) ($_GET['maxWidth'] ?? false);
$minHeight = (int) ($_GET['minHeight'] ?? false);
if(!$minHeight) $minHeight = ($_GET['png'] ?? false) ? 500 : 50;

if ($fluid) {
	$width = $maxWidth;
} else {
	$width = $gCols * $gColWidth + ($gCols - 1) * $gGutter + $gGutterLeftEdge + $gGutterRightEdge;
}

if ($gCols < 1
 || $width > 4000) {
	exit;
}

function imageWithBars(&$img, $gCols, $gColWidth, $gGutter, $gGutterLeftEdge, $r, $g, $b, $a = 0) {
	global $minHeight;
	
	for ($i = 0; $i < $gCols; ++$i) {
		$start = $i * $gColWidth + $i * $gGutter + $gGutterLeftEdge;
		imagefilledrectangle($img->getHandle(), round($start), 0, round($start + $gColWidth - 1), $minHeight, $img->getExactColorAlpha($r, $g, $b, $a));
	}
}

$img = WideImage::createTrueColorImage($width, $minHeight);
imagefilledrectangle($img->getHandle(), 0, 0, $width, $minHeight, $img->getExactColorAlpha(0xfc, 0xac, 0xac, 0x40));

if ($fluid) {
	$gColWidthMax = $maxWidth * (100 - ($gCols - 1) * $gGutter - $gGutterLeftEdge - $gGutterRightEdge) / $gCols / 100;
	$gGutterLeftEdge = $gGutterLeftEdge * $maxWidth / 100;
	$gGutterRightEdge = $gGutterRightEdge * $maxWidth / 100;
	
	imageWithBars($img, $gCols, $gColWidthMax, $gGutter * $maxWidth / 100, $gGutterLeftEdge, 0xca, 0x54, 0x60, 0x40);
	
} else {
	imageWithBars($img, $gCols, $gColWidth, $gGutter, $gGutterLeftEdge, 0xca, 0x54, 0x60, 0x40);
}


if ($gGutterLeftEdge > 0) {
	imagefilledrectangle($img->getHandle(), 0, 0, round($gGutterLeftEdge - 1), $minHeight, $img->getExactColorAlpha(0x7c, 0, 0, 0x40));
}

if ($gGutterRightEdge > 0) {
	imagefilledrectangle($img->getHandle(), round($width - $gGutterRightEdge), 0, $width - 1, $minHeight, $img->getExactColorAlpha(0x7c, 0, 0, 0x40));
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
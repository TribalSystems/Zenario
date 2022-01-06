<?php

require '../../adminheader.inc.php';



$width = 2001;
$height = 18;

$img = WideImage\WideImage::createTrueColorImage($width, $height);
imagefilledrectangle($img->getHandle(), 0, 0, $width, $height, $img->getExactColor(0xff, 0xff, 0xff));

imagefilledrectangle($img->getHandle(), 0, 0, $width-1, 0, $img->getExactColor(0x92, 0x92, 0x92));
imagefilledrectangle($img->getHandle(), 0, $height-1, $width-1, $height-1, $img->getExactColor(0x92, 0x92, 0x92));
//imagefilledrectangle($img->getHandle(), 0, 0, 0, $height-1, $img->getExactColor(0x92, 0x92, 0x92));

foreach ([50 => $height - 2, 10 => 5, 5 => 3] as $step => $tall) {
	for ($x = 0; $x < $width; $x += $step) {
		imagefilledrectangle($img->getHandle(), $x, $height-1 - $tall, $x, $height-1, $img->getExactColor(0x92, 0x92, 0x92));
	}
}

$canvas = $img->getCanvas();
for ($x = 0; $x + 2 < $width; $x += 50) {
	$canvas->useFont(CMS_ROOT. 'zenario/libs/manually_maintained/ofl/Marvel/Marvel-Bold.ttf', 8, $img->allocateColor(0x30, 0x30, 0x30));
	$canvas->writeText($x + 2, 4, $x);
}



$img->output('png');
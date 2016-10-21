<?php
// Allows an admin to download any file
require '../adminheader.inc.php';

$fileId = get('id');
$link = false;

if ($fileId) {
	$link = fileLink($fileId);
	$file = getRow('files', array('filename', 'mime_type', 'size'), $fileId);
}

if (!$link || !$file) {
	echo 'File not found.';
	header('HTTP/1.0 404 Not Found');
	exit;
} else {
	header('Content-type: ' . ifNull($file['mime_type'], 'application/octet-stream'));
	header('Content-Disposition: attachment; filename="' . urlencode($file['filename']) . '"');
	header('Content-Length: ' . filesize($link));
	readfile($link);
}
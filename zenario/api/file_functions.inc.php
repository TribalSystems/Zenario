<?php

//These functions have been moved to an encapsulated class using a PSR-4 autoloader.
//Some global functions pointing to them have been left here for now to not break backwards compatability
//with any client or third-party modules that might still use them.

function trackFileDownload($url) {
	return Ze\File::trackDownload($url);
}
function addFileToDocstoreDir($usage, $location, $filename = false, $mustBeAnImage = false, $deleteWhenDone = true) {
	return Ze\File::addToDocstoreDir($usage, $location, $filename, $mustBeAnImage, $deleteWhenDone);
}
function addFileFromString($usage, &$contents, $filename, $mustBeAnImage = false, $addToDocstoreDirIfPossible = false) {
	return Ze\File::addFromString($usage, $contents, $filename, $mustBeAnImage, $addToDocstoreDirIfPossible);
}
function addFileToDatabase($usage, $location, $filename = false, $mustBeAnImage = false, $deleteWhenDone = false, $addToDocstoreDirIfPossible = false, $imageAltTag = false, $imageTitle = false, $imagePopoutTitle = false) {
	return Ze\File::addToDatabase($usage, $location, $filename, $mustBeAnImage, $deleteWhenDone, $addToDocstoreDirIfPossible, $imageAltTag, $imageTitle, $imagePopoutTitle);
}
function optimizeImage($path) {
	return Ze\File::optimizeImage($path);
}
function optimiseImage($path) {
	return Ze\File::optimiseImage($path);
}
function deleteFile($fileId) {
	return Ze\File::delete($fileId);
}
function deletePublicImage($image) {
	return Ze\File::deletePublicImage($image);
}
function addImageDataURIsToDatabase(&$content, $prefix = '', $usage = 'image') {
	return Ze\File::addImageDataURIsToDatabase($content, $prefix, $usage);
}
function checkDocumentTypeIsAllowed($file) {
	return Ze\File::isAllowed($file);
}
function checkDocumentTypeIsExecutable($extension) {
	return Ze\File::isExecutable($extension);
}
function contentFileLink(&$url, $cID, $cType, $cVersion) {
	return Ze\File::contentLink($url, $cID, $cType, $cVersion);
}
function copyFileInDatabase($usage, $existingFileId, $filename = false, $mustBeAnImage = false, $addToDocstoreDirIfPossible = false) {
	return Ze\File::copyInDatabase($usage, $existingFileId, $filename, $mustBeAnImage, $addToDocstoreDirIfPossible);
}
function docstoreFilePath($fileIdOrPath, $useTmpDir = true, $customDocstorePath = false) {
	return Ze\File::docstorePath($fileIdOrPath, $useTmpDir, $customDocstorePath);
}
function documentTypeIsAllowed($file) {
	return Ze\File::isAllowed($file);
}
function documentMimeType($file) {
	return Ze\File::mimeType($file);
}
function isImage($mimeType) {
	return Ze\File::isImage($mimeType);
}
function isImageOrSVG($mimeType) {
	return Ze\File::isImageOrSVG($mimeType);
}
function getDocumentFrontEndLink($documentId, $privateLink = false) {
	return Ze\File::getDocumentFrontEndLink($documentId, $privateLink);
}
function createFilePublicLink($fileId) {
	return Ze\File::createPublicLink($fileId);
}
function createFilePrivateLink($fileId) {
	return Ze\File::createPrivateLink($fileId);
}
function fileLink($fileId, $hash = false, $type = 'files', $customDocstorePath = false) {
	return Ze\File::link($fileId, $hash, $type, $customDocstorePath);
}
function guessAltTagFromFilename($filename) {
	return Ze\File::guessAltTagFromname($filename);
}
function imageLink(
	&$width, &$height, &$url, $fileId, $widthLimit = 0, $heightLimit = 0, $mode = 'resize', $offset = 0,
	$retina = false, $privacy = 'auto',
	$useCacheDir = true, $internalFilePath = false, $returnImageStringIfCacheDirNotWorking = false
) {
	return Ze\File::imageLink(
		$width, $height, $url, $fileId, $widthLimit, $heightLimit, $mode, $offset,
		$retina, $privacy,
		$useCacheDir, $internalFilePath, $returnImageStringIfCacheDirNotWorking
	);
}

function imageLinkArray(
	$imageId, $widthLimit = 0, $heightLimit = 0, $mode = 'resize', $offset = 0,
	$retina = false, $privacy = 'auto', $useCacheDir = true
) {
	return Ze\File::imageLinkArray(
		$imageId, $widthLimit, $heightLimit, $mode, $offset,
		$retina, $privacy, $useCacheDir
	);
}
function itemStickyImageId($cID, $cType, $cVersion = false) {
	return Ze\File::itemStickyImageId($cID, $cType, $cVersion);
}
function itemStickyImageLink(
	&$width, &$height, &$url, $cID, $cType, $cVersion = false,
	$widthLimit = 0, $heightLimit = 0, $mode = 'resize', $offset = 0,
	$retina = false, $privacy = 'auto', $useCacheDir = true
) {
	return Ze\File::itemStickyImageLink(
		$width, $height, $url, $cID, $cType, $cVersion,
		$widthLimit, $heightLimit, $mode, $offset,
		$retina, $privacy, $useCacheDir
	);
}
function itemStickyImageLinkArray(
	$cID, $cType, $cVersion = false,
	$widthLimit = 0, $heightLimit = 0, $mode = 'resize', $offset = 0,
	$retina = false, $privacy = 'auto', $useCacheDir = true
) {
	return Ze\File::itemStickyImageLinkArray(
		$cID, $cType, $cVersion,
		$widthLimit, $heightLimit, $mode, $offset,
		$retina, $privacy, $useCacheDir
	);
}
function createPpdfFirstPageScreenshotPng($file) {
	return Ze\File::createPpdfFirstPageScreenshotPng($file);
}
function addContentItemPdfScreenshotImage($cID, $cType, $cVersion, $file_name, $setAsStickImage=false) {
	return Ze\File::addContentItemPdfScreenshotImage($cID, $cType, $cVersion, $file_name, $setAsStickImage=false);
}
function plainTextExtract($file, &$extract) {
	return Ze\File::plainTextExtract($file, $extract);
}
function updatePlainTextExtract($cID, $cType, $cVersion, $fileId = false) {
	return Ze\File::updatePlainTextExtract($cID, $cType, $cVersion, $fileId);
}
function updateDocumentPlainTextExtract($fileId, &$extract, &$imgFileId) {
	return Ze\File::updateDocumentPlainTextExtract($fileId, $extract, $imgFileId);
}
function safeFileName($filename, $strict = false) {
	return Ze\File::safeName($filename, $strict);
}
function getPathOfUploadedFileInCacheDir($string) {
	return Ze\File::getPathOfUploadedInCacheDir($string);
}
function fileSizeConvert($bytes) {
	return Ze\File::fileSizeConvert($bytes);
}
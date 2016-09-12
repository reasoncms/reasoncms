<?php
	// results of the 2015 penetration test revealed that file uploads were an attack vector. This script is part of remediating
	// that issue. We now store temp uploads outside the docroot. But, some functionality (image uploads, profile image cropping, etc.)
	// requires we have access to the temp file before the upload finishes. Enter this script - given a temp name, attempts to serve
	// up just the image or other file.

	if (isset($_REQUEST["f"])) {
		$tempfile = $_REQUEST["f"];
		$path = REASON_TEMP_UPLOAD_DIR . $tempfile;

		if ($path != realpath($path)) {
			error_log("possible attack? User submitted [$tempfile] to getTempFile.php");
			die("invalid getTempFile path");
		}

		$imgInfo = @getimagesize($path);

		@ob_end_flush(); // readfile will not present memory issues if we disable output buffering

		if ($imgInfo !== false) {
			header('Content-Type:'.$imgInfo["mime"]);
			header('Content-Length: ' . filesize($path));
			readfile($path);
		} else {
			// 2016-01-04 change -- when we launched this we only used getTempFile to preview images, but it's actually needed in another
			// use case - zencoder jobs. For these we'll just serve up whatever.
			header("Content-Disposition: attachment; filename=\"" . basename($tempfile) . "\"");
			header('Content-Length: ' . filesize($path));
			header("Content-Type: application/octet-stream;");
			readfile($path);
		}
	} else {
		echo "Missing required parameters for this functionality. Consult a developer.";
	}
?>

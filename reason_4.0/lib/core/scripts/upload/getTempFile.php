<?php
	// results of the 2015 penetration test revealed that file uploads were an attack vector. This script is part of remediating
	// that issue. We now store temp uploads outside the docroot. But, some functionality (image uploads, profile image cropping, etc.)
	// requires we have access to the temp image before the upload finishes. Enter this script - given a temp name, attempts to serve
	// up just the image. Other files are not supported.
	if (isset($_REQUEST["f"])) {
		$tempfile = $_REQUEST["f"];
		$path = REASON_TEMP_UPLOAD_DIR . $tempfile;

		if ($path != realpath($path)) {
			error_log("possible attack? User submitted [$tempfile] to getTempFile.php");
			die("invalid getTempFile path");
		}

		$imgInfo = @getimagesize($path);

		if ($imgInfo !== false) {
			header('Content-Type:'.$imgInfo["mime"]);
			header('Content-Length: ' . filesize($path));
			readfile($path);
		} else {
			// could be a bad "f" param. Could be a good param, but not an image. Could be an image but not one we know how to handle.
			echo "You cannot preview this type of file.";
		}
	} else {
		echo "Missing required parameters for this functionality. Consult a developer.";
	}
?>

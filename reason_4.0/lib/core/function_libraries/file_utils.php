<?php
/**
 * @package reason
 * @subpackage function_libraries
 */

/**
 * Convert a readable size like "2MB" to number of bytes it represents. Supports suffixes "B", "KB",
 * "MB", "GB", "TB", "PB", "EB", "ZB", "YB". Invalid/missing suffix will default to "B".
 *
 * @author Tom Feiler
 * @param string $formattedSize
 * @return number of bytes if success; else null
 */
function convertFormattedSizeToNumberOfBytes($formattedSize)
{
    $suffixes = array('B'=>0, 'KB'=>1, 'MB'=>2, 'GB'=>3, 'TB'=>4, 'PB'=>5, 'EB'=>6, 'ZB'=>7, 'YB'=>8);

	$matches = Array();
	$matchSuccess = preg_match('/(\d*) *(\D*)/', $formattedSize, $matches);

	if (!$matchSuccess || $matchSuccess == 0) {
		return null;
	} else {
		$size = intval($matches[1]);
		$units = trim(strtoupper($matches[2]));
		if (!in_array($units, array_keys($suffixes))) {
			$units = "B";
		}
	}

    return $size * pow(1024, $suffixes[$units]);
}

/**
 * Convert a number of bytes to a human readable equivalent like "4MB"
 *
 * @author Tom Feiler
 * @param int $bytes
 * @param int $decimals
 * @return human readable equivalent of number of bytes
 */
function convertNumberOfBytesToFormattedSize($bytes, $decimals = 2) {
	$size = array('B','KB','MB','GB','TB','PB','EB','ZB','YB');
	$factor = floor((strlen($bytes) - 1) / 3);
	return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
}

?>

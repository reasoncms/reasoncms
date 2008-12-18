<?php

class Loki2ScriptFinder
{
	var $files = array();
	var $latest_modified_time = null;
	
	function Loki2ScriptFinder($path)
	{
		$this->latest_modified_time = filemtime(__FILE__);
		
		$d = dir($path);
		if (!$d) // TODO: throw an exception if we upgrade to PHP5
			return false;

		while (false !== $e = $d->read()) {
			if ($e{0} == '.' || substr($e, -3) != '.js')
				continue;

			$path = $d->path.DIRECTORY_SEPARATOR.$e;
			$mtime = filemtime($path);
			if ($mtime > $latest_modified)
				$latest_modified = $mtime;

			$this->_insert_filename($e);
		}
		$d->close();
	}
	
	function _insert_filename($file)
	{
		for ($i = 0; $i < count($this->files); $i++) {
			if ($this->_compare_filenames($this->files[$i], $file) < 0)
				continue;

			for ($j = (count($this->files) - 1); $j >= $i; $j--) {
				$this->files[$j + 1] = $this->files[$j];
			}

			break;
		}

		$this->files[$i] = $file;
	}
	
	/**
	 * Compares the names of two Loki JavaScript files $a and $b to determine
	 * which should be sent to the browser first.
	 *
	 * The rules used are fairly complex but can be easily summarized. (Here
	 * ">" means "higher priority than", i.e. "should be sent before".)
	 *
	 *    Non-Loki scripts > Util scripts > UI scripts
	 *    (Non-Loki scripts are those not matching /^(UI|Util)/)
	 *
	 * Within these categories there are certain files that must be sent before
	 * others. Util.js and UI.js must be present before any of their children can
	 * be present. Util.Function depends on Util.Scheduler as it wraps up some
	 * of Scheduler's functionality, and Util.Array needs Util.Function in
	 * order to place its methods on Array's prototype in a meaningful way. Also,
	 * as a precaution, since UI.Loki ultimately depends on everything, it is
	 * sorted last (i.e. * > UI.Loki.js).
	 */
	function _compare_filenames($a, $b)
	{
		static $priority_util_files = array(
			'Util.js', 'Util.Scheduler.js', 'Util.Function.js', 'Util.Array.js',
			'Util.Node.js', 'Util.Browser.js', 'Util.Element.js',
			'Util.Event.js', 'Util.Object.js', 'Util.OOP.js'
		);

		$a_ut = (0 == strncmp($a, 'Util', 4));
		$a_ui = (0 == strncmp($a, 'UI', 2));
		$b_ut = (0 == strncmp($b, 'Util', 4));
		$b_ui = (0 == strncmp($b, 'UI', 2));

		if (!$a_ut && !$a_ui) {
			return (!$b_ut && !$b_ui)
				? strcasecmp($a, $b)
				: -1;
		} else if (!$b_ut && !$b_ui) {
			return 1;
		} else if ($a_ut) {
			if ($b_ui)
				return -1;

			foreach ($priority_util_files as $special_file) {
				if ($a == $special_file)
					return -1;
				if ($b == $special_file)
					return 1;
			}

			return strcasecmp($a, $b);
		} else if ($b_ut) {
			if ($a_ui)
				return 1;
			else
				return strcasecmp($a, $b);
		} else if ($a == 'UI.js') {
			return -1;
		} else if ($b == 'UI.js') {
			return 1;
		} else if ($a == 'UI.Loki.js') {
			return 1;
		} else if ($b == 'UI.Loki.js') {
			return -1;
		} else {
			return strcasecmp($a, $b);
		}
	}
}

?>
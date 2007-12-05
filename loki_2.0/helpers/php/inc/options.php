<?php

/* LOKI EDITING OPTIONS */

/**
 * Represents the various options available to Loki. 
 *
 * NOTE: This is no longer used anywhere except by Reason, and even in Reason
 * it's only used (as far as I know) in Master Admin :: site, to generate
 * the list of available options.
 *
 * The list of options used everywhere else is located in js/UI.Loki_Options.js
 * Be sure to keep the list in this file synced with that in js/UI.Loki_Options.js!
 *
 * @todo  Get rid of this file, and instead write a script to pull from js/UI.Loki_Options
 * automatically.
 */
class Loki2_Options
{
	var $_all;
	var $_sel;

	// Both $pluses and $minuses can be either arrays or strings. E.g.:
	//  new Loki_Options( 'all' );
	//	new Loki_Options( 'default', array('table', 'hrule') );
	//  new Loki_Options( array('strong', 'em', 'linebreak') );
	function Loki2_Options($pluses = array(), $minuses = array())
	{
		$this->_init_all();
		$this->_init_sel($pluses, $minuses);
	}

	// Must be a string containing the name of one option
	function is_sel($option)
	{
		return ( !empty($this->_all[$option]) && $this->_sel & $this->_all[$option] );
	}

	// Returns an array with all available options
	function get_all()
	{
		return $this->_all;
	}
	
	// Initializes the array of all the options
	function _init_all()
	{
		$a = &$this->_all;
		$a = array( 'strong' => 1,
					'em' => 2,
					'headline' => 4,
					'linebreak' => 8,
					'alignleft' => 16,
					'aligncenter' => 32,
					'alignright' => 64,
					'olist' => 128,
					'ulist' => 256,
					'indenttext' => 512,
					'findtext' => 1024,
					'link' => 2048,
					'table' => 4096,
					'image' => 8192,
					'assets' => 16384,
					'source' => 32768,
					'anchor' => 65536,
					'hrule' => 131072,
					'spell' => 262144,  
					'merge' => 524288,  // This shouldn't be included in 'default' or 'all'
					'pre' => 1048576
		);

		$a['default'] = $a['strong'] + $a['em'] + $a['linebreak'] + $a['hrule'] + $a['link'] + $a['anchor'];
		$a['lists'] = $a['olist'] + $a['ulist'];
		$a['alignment'] = $a['alignleft'] + $a['aligncenter'] + $a['alignright'];

		$a['all'] = $a['default'] + $a['lists'] + $a['alignment'] + $a['headline'] + $a['indenttext'] + $a['findtext'] + $a['image'] + $a['assets'] + $a['spell'] + $a['table'] + $a['pre'];
		$a['all_minus_pre'] = $a['all'] - $a['pre'];
		$a['notables'] = $a['all_minus_pre'] - $a['table'];
		$a['notables_plus_pre'] = $a['notables'] + $a['pre'];

		$a['wellstone'] = $a['default'] + $a['lists'] + $a['alignment'] + $a['headline'] + $a['indenttext'] + $a['findtext'];
		$a['ocs'] = $a['default'] + $a['lists'] + $a['alignment'] + $a['headline'] + $a['indenttext'] + $a['findtext'] + $a['table'];
		$a['commencement'] = $a['default'] + $a['lists'] + $a['alignment'] + $a['headline'] + $a['indenttext'] + $a['findtext'] + $a['table'];
	}
		
	function _init_sel($pluses, $minuses)
	{
		if ( empty($pluses) && empty($minuses) )
			$this->_sel += $this->_all['default'];
		else
		{
			if ( !is_array($pluses) )
				$pluses = array($pluses);
			if ( !is_array($minuses) )
				$minuses = array($minuses);

			$this->_sel = 0;

			foreach ( $pluses as $plus )
				$this->_sel += $this->_all[$plus];
			foreach ( $minuses as $minus )
				$this->_sel -= $this->_all[$minus];
		}
	}
}

?>
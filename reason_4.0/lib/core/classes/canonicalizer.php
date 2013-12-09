<?php
/**
 * @package reason
 * @subpackage classes
 * @author Steven L. Smith, Benjamin M. Wilbur
 */

reason_include_once( 'classes/head_items.php' );

/**
 * Class to track modules on a page
 * and subsequently remove unimportant/non-canonical
 * query string parameters and set the result as the
 * canonical url for search engines.
 */
class reasonCanonicalizer
{
	protected $modules = array();
	protected $num_canonical_mods = 0;
	protected $data = array();
	/**
	 * Lets the canonicalizer know what modules are on the page.
	 * @param DefaultMinisiteModule $module 
	 */
	public function register($module)
	{
		$this->modules[] = $module;
	}
	/**
	 * @return string|NULL Canonical url, NULL if current page is canonical
	 */
	public function get_canonical_url()
	{
		foreach($this->modules as $key=>$module)
		{
			$this->data[$key]['non_canonical'] = $module->get_noncanonical_request_keys();
			$this->data[$key]['all'] = array_keys($module->get_cleanup_rules());
			$this->data[$key]['canonical'] = array_diff($this->data[$key]['non_canonical'], $this->data[$key]['all']);
		}
		$canonicalized_url = NULL;
		$non_cans_array = $this->get_non_canonical_url_params();
		$curr_url = get_current_url();
		$parsed_url = parse_url($curr_url);
		$non_cans_array = array_flip($non_cans_array);
		$canonicalized_url = $this->strip_non_canonical_url_params($non_cans_array, $parsed_url);

		if ($canonicalized_url == get_current_url())
		{
			return;
		} 
		else 
		{
			return trim_slashes($canonicalized_url);
		}
	}

	/**
	 * @param array $non_cans_array non-canonical query string parameters from registered modules
	 * @param array $parsed_url
	 * @return string Canonicalized url 
	 */
	private function strip_non_canonical_url_params($non_cans_array, $parsed_url)
	{
		if (array_key_exists('query', $parsed_url))
		{
			parse_str($parsed_url['query'], $qs_arrray);
		} 
		else 
		{
			$qs_arrray = array();
		}
		$canonicalized_qs = array_diff_key($qs_arrray, $non_cans_array);
	 	$canonicalized_qs = http_build_query($canonicalized_qs);
	 	$canonicalized_url = $parsed_url['scheme'];
	 	$canonicalized_url .= '://';
	 	$canonicalized_url .= $parsed_url['host'];
	 	if (isset($parsed_url['port']))
	 	{
	 		$canonicalized_url .= $parsed_url['port'];
	 	}
 		$canonicalized_url .= $parsed_url['path'];
	 	if (!empty($canonicalized_qs))
	 	{
		 	$canonicalized_url .= '?';
		 	$canonicalized_url .= $canonicalized_qs;
		}
		return $canonicalized_url;
	}
	/**
	 * @return array All non-canonical query string parameters from all modules
	 */
	private function get_non_canonical_url_params()
	{
		$return = array();
		foreach ($this->data as $module => $canon_types) {
			foreach ($canon_types as $canon_type=>$params) {
				if ($canon_type == 'non_canonical' && !empty($canon_types['non_canonical']))
				{
					foreach ($params as $param)
					{
						array_push($return, $param);
					}
				}
			}
		}
		return $return;
	}
}
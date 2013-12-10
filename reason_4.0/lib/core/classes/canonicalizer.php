<?php

class reasonCanonicalizer
{
	protected $modules = array();
	protected $num_canonical_mods = 0;
	public function register($module)
	{
		$this->modules[] = $module;
	}
	public function get_canonical_url()
	{
		$full_request_keys = array_keys($_GET);
		$data = array();
		foreach($this->modules as $key=>$module)
		{
			$data[$key]['non_canonical'] = $module->get_noncanonical_request_keys();
			$data[$key]['all'] = array_keys($module->get_cleanup_rules());
			$data[$key]['canonical'] = array_diff($non_canonical, $all);
			if (!empty($data[$key]['canonical']))
			{
				$num_canonical_mods ++;
			}

		}
		// logic goes here
		if ($num_canonical_mods == 1) //follow left side of pdf
		{

		} 
		else //follow right side
		{
			$this->get_non_conanical_query_string_params();
		}

		// return $modules[$key]->get_canonical_url();

		return '/foo/bar/';
	}

	private function get_non_conanical_query_string_params()
	{
		
	}
}
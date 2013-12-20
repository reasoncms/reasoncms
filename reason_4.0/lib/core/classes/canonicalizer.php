<?php

class reasonCanonicalizer
{
	protected $modules = array();
	protected $num_canonical_mods = 0;
	protected $data = array();
	public function register($module)
	{
		$this->modules[] = $module;
	}
	public function get_canonical_url()
	{
		foreach($this->modules as $key=>$module)
		{
			$this->data[$key]['non_canonical'] = $module->get_noncanonical_request_keys();
			$this->data[$key]['all'] = array_keys($module->get_cleanup_rules());
			// $this->data[$key]['canonical'] = array_diff($non_canonical, $all);
			$this->data[$key]['canonical'] = array_diff($this->data[$key]['non_canonical'], $this->data[$key]['all']);
			if (!empty($this->data[$key]['non_canonical']))
			{
				$this->num_canonical_mods++;
				$canonical_module = $module;
			}
		}
		// logic goes here
		if ($this->num_canonical_mods == 1) //follow left side of pdf
		{
			// echo $canonical_module->get_canonical_url();
			return $canonical_module->get_canonical_url();
			// return '/foo/bar/';
		} 
		else //follow right side
		{
			$params_string = array();
			$non_cans = $this->has_non_canonical_url_params(); // Any non-canonical QSPs?
			if (!empty($non_cans))
			{
				//Add rel=canonical
				//Strip non-canonical QSPs from URL
				// Stringify all canonical QSPs
				$params_string = implode('&', $this->get_canonical_url_params());
			}
			return $params_string;
		}
	}

	private function has_non_canonical_url_params()
	{
		// $params = array();
		foreach ($this->data as $module => $canon_type) {
			if ($canon_type == 'non_canonical' && (!empty($canon_type['non_canonical']))){
				// foreach ($canon_type as $url_param) {
						return true;
					// array_push($params, $url_param);
				// }
			}
		}
		// return $params;
	}

	private function get_canonical_url_params()
	{
		$params = array();
		foreach ($this->data as $module => $canon_type) {
			if ($canon_type == 'canonical' && (!empty($canon_type['non_canonical']))){
				foreach ($canon_type as $url_param) {
					array_push($params, $url_param);
				}
			}
		}
		return $params;
	}


}
class reasonCanonicalizer
{
	protected $modules = array();
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
		}
		// logic goes here

		// return $modules[$key]->get_canonical_url();

		return '/foo/bar/';
	}
}
<?

class submodule
{
	var $request = array();
	var $params = array();
	var $site;
	var $additional_vars = array();
	function pass_params($params)
	{
		foreach($params as $key=>$value)
		{
			if(array_key_exists($key,$this->params))
			{
				$this->params[$key] = $value;
			}
		}
	}
	function pass_site($site)
	{
		$this->site = $site;
	}
	function pass_additional_vars($vars)
	{
		$this->additional_vars = $vars;
	}
	function init($request)
	{
		if(!empty($request))
		{
			$this->request = $request;
		}
	}
	function has_content()
	{
		return true;
	}
	function get_content()
	{
		trigger_error('this must be overloaded');
	}
}
?>
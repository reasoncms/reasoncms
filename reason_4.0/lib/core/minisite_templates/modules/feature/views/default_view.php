<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

//$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'DefaultView';


/**
 * NOTE - Features are a beta feature. This abstract class and any views distributed with Reason 4 are subject to change.
 * 
 * It is recommended that you DO NOT create custom feature views at this time.
 * 
 * This is the root class for all view layer views of the Feature Module.
 * 
 * To create a new view you should override the following methods
 * 	set($view_data,$view_params,$current_feature_id,&$head_items)
 *   get_html()
 *
 * @todo the set method is out of control - fix it.
 */
class FeatureView
{
	
	var $_view_params;//parameters that control how the view layer behaves
	var $_view_data;//the data to be shown to the user via the view layer
	var $current_feature_id=0;//which feature to show first
	var $default_width=400;//in case width wasn't passed in via set function
	var $default_height=300;//in case height wsn't passed in via set function
	
	/**
	* This function should be called right before get_html()
	* it sets the parameters of how the object will behave
	* as well as providing the data to be displayed.
	*
	*
	* @param $features - an array of feature objects
	* @param $view_params - an array of view options
	* @param $current_feature_id - the id of the feature to be shown (0 means first feature in $view_data array is shown)
	* @param &$head_items allows the markup object to put needed css into the header
	*/
	function set($view_data,$view_params,$current_feature_id,&$head_items)
	{
		$this->current_feature_id=$current_feature_id;
		//Make sure all view parameters have default values
		//if they haven't been set
		$this->_view_params=$view_params;

		if(!isset($view_params['autoplay_timer']))
		{
			$this->_view_params['autoplay_timer']=0;
		}
		if(!isset($view_params['condensed_nav']))
		{
			$this->_view_params['condensed_nav']=false;
		}
		if(!isset($view_params['looping']))
		{
			$this->_view_params['looping']='on';
		}
		if(!isset($view_params['width']))
		{
			$this->_view_params['width']=$this->default_width;
		}
		if(!isset($view_params['height']))
		{
			$this->_view_params['height']=$this->default_height;
		}
		if(!isset($view_params['absolute_urls']))
		{
			$this->_view_params['absolute_urls']=false;
		}
	}// end set function
	
	function absolutify_url_if_needed($url)
	{
		if($this->_view_params['absolute_urls'])
		{
			// If it starts with a slash but not two slashes, add the domain
			if(strpos($url, '/') === 0 && strpos($url, '//') !== 0)
				return '//' . HTTP_HOST_NAME . $url;
			//Otherwise, return as-is
			return $url;
		}
		return $url;
	}
	
	
	/**
	* returns view parameters
	*/
	function get_view_params()
	{
		return $this->_view_params;
	}
	
	/**
	* return view layer data
	*/
	function get_view_data()
	{
		return $this->_view_data;
	}

	/**
	 * Create the markup
	 * @return string containing html markup for features
	 * Function should be overloaded by child classes
	 */
	function get_html()
	{
		$str="";
		return $str; 
	}
}

?>
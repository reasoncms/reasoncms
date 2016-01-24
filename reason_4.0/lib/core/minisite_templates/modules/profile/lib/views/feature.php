<?php
include_once( 'reason_header.php' );
include_once( CARL_UTIL_INC . 'basic/url_funcs.php' );
reason_include_once( 'classes/mvc.php' );
reason_include_once( 'function_libraries/images.php' );
reason_include_once( 'function_libraries/image_tools.php' );
$GLOBALS[ '_profiles_view' ][ basename( __FILE__, '.php' ) ] = 'DefaultProfileFeatureView';

/**
 * The data for this view is a ProfileFeatureSet object.
 *
 * - If empty we show a default.
 * - If less than number allowed in config we show an "Add" link.
 * - If we are in edit view we show an edit link.
 * 
 */
class DefaultProfileFeatureView extends ReasonMVCView
{
	var $str;
	var $config = array('edit' => false, 'feature_id' => NULL);
	
	/**
	 * @todo we need a meaningful ALT tag.
	 */
	function get()
	{ 
		$feature_set = $this->data();
		// we are in edit view and editing a particular image
		if ($this->config('edit') && ($this->config('feature_id')))
		{
			// show current
			$str = '<h3>Replace Image:</h3>';
			$str .= '<img src="' . reason_get_image_url($feature_set->features[$this->config('feature_id')]->entity, 'thumbnail') . '?z='.md5( uniqid (rand(), 1) ).'" />';
			// feature render image edit and crop form
			$edit_section = $feature_set->get_edit_section($this->config('feature_id'));
			$edit_section->set_display_name('Replace Feature');
			$edit_section->set_crop(1200, 400);
			$str .= $edit_section->run();
		}
		
		// we are in edit view but not a particular feature - show the set with edit links and an upload option is more are available.
		elseif ($this->config('edit') && (is_null($this->config('feature_id'))))
		{
			$str = '<ul class="slides">';
			foreach ($feature_set->features as $k => $feature)
			{
				$str .= '<li>';
				$str .= '<a href="'.carl_make_link(array('feature_id' => $feature->entity->id())).'"><img src="' . reason_get_image_url($feature->entity, 'thumbnail') . '?z='.md5( uniqid (rand(), 1) ).'" />Edit</a>';
				$str .= '</li>';
			}
			$str .= '</ul>';
			
			if (count($feature_set->features) < 4)
			{
				$add_section = $feature_set->get_add_section();
				$add_section->set_display_name('Add a Feature');
				$add_section->set_crop(1200, 400);
				$str .= $add_section->run();
			}
		}
		else
		{
			if (empty($feature_set->features))
			{
				$str = '<p>Placeholder!</p>';
			}
			else
			{	
				$str = '<div class="flexslider">';
				$str .= '<ul class="slides">';
				foreach ($feature_set->features as $k => $feature)
				{
					$str .= '<li>';
					$str .= '<img src="' . reason_get_image_url($feature->entity) . '?z='.md5( uniqid (rand(), 1) ).'" />';
					$str .= '</li>';
				}
				$str .= '</ul>';
				$str .= '</div>';
				$str .= '<script type="text/javascript" charset="utf-8">';
  				$str .= '$(window).load(function() {';
    			$str .= "$('.flexslider').flexslider();";
    			$str .= '});';
    			$str .= '</script>';
			}
		}
		return $str;
	}
}
?>
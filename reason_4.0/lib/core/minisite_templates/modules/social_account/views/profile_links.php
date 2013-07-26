<?php
/**
 * @package reason
 * @subpackage classes
 */
 
/**
 * Include the base class
 */
include_once( 'reason_header.php' );
reason_include_once( 'classes/mvc.php' );

/**
 * Register MVC component with Reason
 */
$GLOBALS[ '_reason_mvc_view_class_names' ][ reason_basename(__FILE__) ] = 'ReasonSocialProfileLinksView';

/**
 * ReasonSocialProfileLinksView displays data from models that provide an array of profile link information structured like this:
 *
 * ID ->
 *	- icon
 *  - text
 *  - href
 *
 * @author Nathan White
 */
 
class ReasonSocialProfileLinksView extends ReasonMVCView
{
	function get()
	{
		$profile_links = $this->data();
		if (!empty($profile_links))
		{
			$str = '<div class="socialProfileLinks">';
			foreach ($profile_links as $id => $link)
			{
				$space = (isset($space)) ? " " : "";
				$str .= $space . '<a href="'.$link['href'].'"><img src="'.$link['icon'].'" alt="'.$link['text'].'" /></a>';	
			}
			$str .= '</div>';
		}
		else $str = '';
		return $str;
	}
}
?>
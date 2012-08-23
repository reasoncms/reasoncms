<?php
/**
 * Interface for all theme customizers.
 * @package reason
 * @subpackage theme_customizers
 */
 
/**
 * Interface for all theme customizers.
 *
 * @author Matt Ryan
 */
interface reasonThemeCustomizerInterface
{
	/**
	 * Set stored customization data
	 *
	 * @param object $data Unserialized json data
	 * @return void
	 */
	public function set_customization_data($data);
	
	/**
	 * Get data in the form to be stored
	 *
	 * If given a submitted disco form, this method transforms the form into the appropriate form.
	 *
	 * Otherwise, this method returns the data set on the object (if available)
	 *
	 * @param object $disco
	 * @return mixed stdClass object Data if available, NULL if not
	 */
	public function get_customizaton_data($disco = false);
	
	/**
	 * Set up the theme customization form
	 *
	 * @param object $disco
	 * @return void
	 */
	public function modify_form($disco);
	
	/**
	 * Modify the head items of a Reason page
	 *
	 * This is the primary (and simplest) way for a theme customizer to affect the look of a site.
	 *
	 * @param object $head_items
	 * @return void
	 */
	public function modify_head_items($head_items);
	
	/**
	 * Can a given user modify the theme?
	 *
	 * Theme customizers can specify which users are allowed to modify the theme. This allows the
	 * administrative interface to only offer modification links to those 
	 *
	 * Note that users with the privilege customize_all_themes bypass this check.
	 *
	 * Note also that users without access to the site are blocked from theme customization by
	 * default.
	 *
	 * Therefore, if this method simply returns false, only administrators will be able to customize the theme. If it simply returns true, administrators and site users will be able to customize the
	 * theme. Of course, more sophisticated logic could be implemented allowing only certain users
	 * or roles to customize the theme.
	 *
	 * @param integer $user_id
	 * @return boolean
	 */
	public function user_can_customize($user_id);
}
?>
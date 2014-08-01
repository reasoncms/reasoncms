<?php
/**
 * @package reason_local
 * @subpackage minisite_modules
 */

/**
 * Include the reason header, and register the module with Reason
 */
include_once( 'reason_header.php' );

/**
 * Include dependencies
 */
include_once( DISCO_INC . 'disco.php' );
include_once( DISCO_INC . 'boxes/linear.php' );

/**
 * Logic for all profile edit disco forms.
 *
 * @author Nathan White
 */
class defaultProfileEditForm extends Disco
{
	var $person;
	var $section;
	var $actions = array('Save');
	var $box_class = 'LinearBox';
	var $strip_tags_from_user_input = true;
	var $error_header_text = 'Oops! Please check what you entered.';
	
	function get_person()
	{
		return $this->person;
	}

	function set_person($person)
	{
		$this->person = $person;
	}
	
	function get_section()
	{
		return $this->section;
	}
	
	function set_section($str)
	{
		$this->section = $str;
	}
	
	function get_section_display_name()
	{
		return $this->section_display_name;
	}
	
	function set_section_display_name($str)
	{
		$this->section_display_name = $str;
	}

	function get_head_items()
	{
		return $this->head_items;
	}
		
	function set_head_items($head_items)
	{
		$this->head_items = $head_items;
	}
	
	/**
	 * Redirect without an editable field specified.
	 */
	function where_to()
	{
		$redirect = carl_make_redirect(array('edit_section' => ''));
		return $redirect;
	}
}
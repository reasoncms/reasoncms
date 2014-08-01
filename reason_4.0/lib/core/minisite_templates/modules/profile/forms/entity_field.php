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
reason_include_once( 'minisite_templates/modules/profile/forms/default.php' );
include_once ( CARL_UTIL_INC . 'tidy/tidy.php' );
include_once ( CARL_UTIL_INC . 'basic/cleanup_funcs.php' );

/**
 * Profile Module default edit form.
 *
 * - Creates a TinyMCE WYSIWYG textarea that saves back to the entity.
 * - The section name set must map to an entity field in the profile type.
 *
 * @author Nathan White
 * @todo use tinymce html editor integration class when it is complete and customizable
 */
class entityFieldProfileEditForm extends defaultProfileEditForm
{
	var $allowable_HTML_tags = '<em><strong><h3><h4><p><ul><ol><li><a><blockquote>';
	
	var $buttons = array(
		'formatselect',
		'bold',
		'italic',
		'blockquote',
		'numlist',
		'bullist',
		'indent',
		'outdent',
		'link',
		'unlink',
	);
	
	var $formatselect_options = array('p','h4');
	
	var $init_options = array(
		'auto_focus' => 'mce_editor_0'
	);
	
	/**
	 * Setup TinyMCE.
	 */
	function on_every_time()
	{
		$person = $this->get_person();
		$section = $this->get_section();
		
		$this->add_element($section, 'tiny_mce_no_label', array(
			'buttons' => $this->buttons,
			'formatselect_options' => $this->formatselect_options,
			'init_options' => $this->init_options,
			'content_css' => REASON_HTTP_BASE_PATH . 'modules/profiles/tiny_mce.css',
			'rows' => 8,
			)
		);
						   							
		$value = $person->get_profile_field($section);
		if (!empty($value)) $this->set_value($section, $value);
	}
	
	/**
	 * Update our profile field.
	 */
	function process()
	{
		$person = $this->get_person();
		$section = $this->get_section();
		
		$value = $this->get_value($section);
		$prepped_value = trim(carl_get_safer_html(tidy($value)));
		
		// tidy seems to create a bunch of blank lines <p>&nbsp;</p> - lets strip them ... do we have this issue with loki? Or is it TinyMCE making loose &nbsp?
		$prepped_value = str_replace('<p>&nbsp;</p>', '', $prepped_value);
		
		$person->update_profile_entity_field($section, $prepped_value);
	}
}
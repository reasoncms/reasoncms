<?php

/**
 * Content manager for Audio/Video/Multimedia Files
 * @package reason
 * @subpackage content_managers
 */

/**
 * Include dependencies
 */
reason_include_once('content_managers/default.php3');
reason_include_once('classes/media/factory.php');

include_once(CARL_UTIL_INC . 'basic/mime_types.php');

/**
 * Define the class name so that the admin page can use this content manager
 */
$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'avFileManager';

/**
 * Content manager for Audio/Video/Multimedia Files
 *
 * Handles importing of media if Reason is set up for it
 */
class avFileManager extends ContentManager
{
	/**
	* The content manager modifier for this media file
	*/
	protected $modifier;
	
	/**
	* Initialize the modifier for this content managaer.
	*/
	function init($externally_set_up = false)
	{
		// grab the media work this media file is associated with
		$es = new entity_selector();
		$es->add_type(id_of('av'));
		$es->add_left_relationship($this->get_value('id'), relationship_id_of('av_to_av_file'));
		$media_work = current($es->run_one());
		
		$integration_library = '';
		if ($media_work)
			$integration_library = $media_work->get_value('integration_library');
			
		$this->modifier	= MediaWorkFactory::media_file_content_manager_modifier($integration_library);
		if ($this->modifier)
		{
			$this->modifier->set_content_manager($this);
			$this->modifier->set_head_items();
			// attach callbacks for the form
			$this->modifier->process();
			$this->modifier->run_error_checks();
		}
		parent::init();
	}
	
	/**
	* Displays all of the fields in the form.
	*/
	function show_form()
	{	
		if ($this->modifier)
			$this->modifier->show_form();
		else
			echo '<p>This content manager is unavailable.</p>';
	}

	/**
	* Changes the form data.
	*/
	function alter_data()
	{
		if ($this->modifier)
			$this->modifier->alter_data();	
	}
	
	// The following are functions that allow the modifier to call the parent's functions. Given an
	// objects, I don't believe it's possible to call its parent's functions without a workaround
	// like this.
	function _parent_show_form()
	{
		parent::show_form();
	}
}
?>
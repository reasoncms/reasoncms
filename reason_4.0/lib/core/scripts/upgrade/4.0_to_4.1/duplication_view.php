<?php
/**
 * @package reason
 * @subpackage scripts
 */
include_once('reason_header.php');
reason_include_once('classes/entity_selector.php');
reason_include_once('classes/upgrade/upgrader_interface.php');

$GLOBALS['_reason_upgraders']['4.0_to_4.1']['duplication_view'] = 'ReasonUpgrader_41_DuplicationView';

/**
 * Not sure
 */
class ReasonUpgrader_41_DuplicationView implements reasonUpgraderInterface
{
	protected $user_id;
	
	// do we really need this? perhaps we should have a default these extend that define this since it seems to
	// be always the same.
	public function user_id( $user_id = NULL)
	{
		if(!empty($user_id))
			return $this->_user_id = $user_id;
		else
			return $this->_user_id;
	}
	
        /**
         * Get the title of the upgrader
         * @return string
         */
	public function title()
	{
		return 'Create view type and view entity for the duplication view..';
	}
        /**
         * Get a description of what this upgrade script will do
         * @return string HTML description
         */
	public function description()
	{
		return 'This upgrader creates a view type and view entity to support entity duplication. It assigns the form 
				type to use this new view, allowing easy duplication of existing forms.';
	}
        /**
         * Do a test run of the upgrader
         * @return string HTML report
         */
	public function test()
	{
		if (!$this->view_type_id())
		{
			echo '<p>Would create the view type "List With Duplicate"</p>';
		}
		if (!$this->view_id())
		{
			echo '<p>Would create the view "List View with Duplicate"</p>';
		}	
		if (!$this->view_id() || !$this->view_type_id())
		{
			echo '<p>Would ensure necessary relationships are in place.</p>';
		}
		else
		{
			echo '<p>This script has already run.</p>';
		}
	}
        /**
         * Run the upgrader
         *
         *
         * @return string HTML report
         */
	public function run()
	{
		if ($view_id = $this->view_id()) echo '<p>The view already exists - its id is ' . $view_id . '</p>';
		else
		{
			$view_id = $this->create_view();
			if ($view_id) echo '<p>Created "List View with Duplicate" view with id ' . $view_id . '</p>';
		}
		
		
		if ($view_type_id = $this->view_type_id()) echo '<p>The view type already exists - its id is ' . $view_type_id . '</p>';
		else
		{
			$view_type_id = $this->create_view_type();
			if ($view_type_id) echo '<p>Created "List with Duplicate" view type with id ' . $view_type_id . '</p>';
		}
		
		
		if (create_relationship( $view_id, $view_type_id, relationship_id_of('view_to_view_type')))
		{
			echo '<p>Created view to view type relationship.</p>';
		}
		else echo '<p>The view is already related to the view type.</p>';
		
		if (create_relationship( $view_id, id_of('form'), relationship_id_of('view_to_type')))
		{
			echo '<p>Created view to type (form) relationship.</p>';
		}
		else echo '<p>The view is already related to the form type.</p>';
		
		if (create_relationship( id_of('form'), $view_id, relationship_id_of('type_to_default_view')))
		{
			echo '<p>Created type (form) to default view relationship.</p>';
		}
		else echo '<p>The form type already has the proper default view.</p>';
	}
	
	private function create_view_type()
	{
		return reason_create_entity( id_of('master_admin'), id_of('view_type'), $this->user_id(), 'List with Duplicate', array('new'=>0,'url'=>'list_with_duplicate.php'));	
	}
	
	private function create_view()
	{
		return reason_create_entity( id_of('master_admin'), id_of('view'), $this->user_id(), 'List View with Duplicate', array('new'=>0));
	}
	
	/**
	 * @return mixed id of view_type or false
	 */
	private function view_type_id()
	{
		$es = new entity_selector(id_of('master_admin'));
		$es->add_type(id_of('view_type'));
		$es->add_relation('url.url = "list_with_duplicate.php"');
		$result = $es->run_one();
		if (!empty($result))
		{
			$view_type = reset($result);
			return $view_type->id();
		}
		return false;
	}
	
	/**
	 * @return mixed id of view or false
	 */
	private function view_id()
	{
		$es = new entity_selector(id_of('master_admin'));
		$es->add_type(id_of('view'));
		$es->add_relation('entity.name = "List View with Duplicate"');
		$result = $es->run_one();
		if (!empty($result))
		{
			$view = reset($result);
			return $view->id();
		}
		return false;
	}
}
?>

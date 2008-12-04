<?
include_once('reason_header.php');
include_once(CARL_UTIL_INC . 'db/table_admin.php');
$GLOBALS[ '_form_admin_view_class_names' ][ basename( __FILE__, '.php') ] = 'DefaultDBAdminForm';

/**
 * Default DB Admin Form
 *
 * Sets default allowable action to view only, accept and return reference to model
 *
 * @todo consider move away from the standard DiscoDB based DiscoDefaultAdmin for save / edit / delete and use model methods instead
 *
 * @author Nathan White
 */

 class DefaultDBAdminForm extends DiscoDefaultAdmin
 {			
 	/**
 	 * The class requires a model
 	 */
	var $_model;

	var $allowable_actions = array ('view' => true);
	
	/**
	 *  Accepts a reference to the model
	 */
	function set_model(&$model)
	{
		$this->_model =& $model;
	}
	
	function setup_form(&$table_admin)
	{
		if (method_exists($this, 'custom_init')) $this->custom_init();
		parent::setup_form(&$table_admin);
	}
	
	/**
	 * Return a reference to the model
	 */
	function &get_model()
	{
		return $this->_model;
	}
}
?>
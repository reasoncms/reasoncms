<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */

	/**
	 * Include base class & register controller with Reason
	 */
	reason_include_once( 'minisite_templates/modules/form/controllers/default.php' );
	include_once(TYR_INC . 'tyr.php');
	$GLOBALS[ '_form_controller_class_names' ][ basename( __FILE__, '.php') ] = 'ThorFormController';

	/**
	 * ThorFormController
	 *
	 * Provides a custom init_admin and init_summary method 
	 *
	 * @todo implement data models in table admin and deprecate me - thor can just use the default controller
	 * @author Nathan White
	 *
	 */
	class ThorFormController extends DefaultFormController
	{
		function delete_row($row_id) {
			$model =& $this->get_model();
			$tc = $model->get_thor_core_object();
			$username = reason_check_authentication();

			$ok_to_delete = false;
			if ($model->user_has_administrative_access()) {
				$ok_to_delete = true; // user is an admin - allow it
			} else {
				$vals = $tc->get_values_for_primary_key($row_id);
				if (@$vals["submitted_by"] == $username) {
					$ok_to_delete = true; // user submitted this row - allow it
				}

			}
				
			if ($ok_to_delete) {
				$tc->delete_by_primary_key($row_id);
				echo "<font color='red'>Row $row_id has been deleted.</font><p>";
			} else {
				trigger_error("user $username tried to delete row $row_id on this form");
			}
		}

		function run() {
			$model = $this->get_model();

			if ($model->user_has_administrative_access() && $model->user_requested_admin()) { // run normal admin mode stuff if it was requested
				parent::run();
			} else if (@$_REQUEST["table_row_action"] == "delete") { // end-user deletion, NOT admin deletion - run some custom code
				if ($model->is_deletable()) {
					$row_id = @$_REQUEST["table_action_id"];
					if (@$_REQUEST["confirm_delete"] == "yes") {
						$this->delete_row($row_id);

						$model =& $this->get_model();
						if ($model->form_allows_multiple()) {
							$tc = $model->get_thor_core_object();
							$user_rows = $tc->get_values_for_user(reason_check_authentication());

							if ($user_rows !== false) {
								$list_link = carl_construct_link();
								echo "<a href='$list_link'>View Your Submission List.</a><br>";
							}
						}
						$create_link = carl_construct_link(Array("form_id" => 0));
						echo "<a href='$create_link'>Create New Form Submission.</a><br>";
					} else {
						$confirm_delete = carl_construct_link(array('confirm_delete' => 'yes'), array('table_row_action', 'table_action_id'));
						$cancel_delete = carl_construct_link();
						echo "Do you really want to delete this entry? This cannot be undone!<P>";

						$tc = $model->get_thor_core_object();
						$data = $tc->get_values_for_primary_key($row_id);
						unset($data["id"]);
						$data = $tc->transform_thor_values_for_display($data);
						if ($data) {
							// we are going to use Tyr to format this up though it is a little silly ...
							$tyr = new Tyr();
							$html = $tyr->make_html_table($data, false);
							echo $html;
						} else {
							echo '<p>No data can be displayed for this row.</p>';
						}

						echo "<a href='$confirm_delete'>Yes, delete this entry.</a><br>";
						echo "<a href='$cancel_delete'>No, leave this entry alone.</a>";
					}
				} else {
					trigger_error("form does not support deletion but flow was attempted.");
				}
			} else { // run default behavior
				parent::run();
			}
		}

		/**
		 * Default admin view gets a thor table admin object and inits it
		 */
		function init_admin()
		{
			$model =& $this->get_model();
			$head_items =& $model->get_head_items();
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/forms/form_data.css');
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/hide_nav.css');
			$admin =& $model->get_admin_object();
			$admin->init_thor_admin();
		}
		
		/**
		 * Default summary view gets a table admin object and sets its data
		 */
		function init_summary()
		{
			$model =& $this->get_model();
			$head_items =& $model->get_head_items();
			$head_items->add_stylesheet(REASON_HTTP_BASE_PATH.'css/forms/form_data.css');		
			$summary =& $model->get_summary_object();
			$user_values = $model->get_values_for_user_summary_view();
			$summary->set_data_from_array($user_values);		
		}
	}
?>

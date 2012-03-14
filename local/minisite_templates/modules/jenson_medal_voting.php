<?php
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'JensonMedalModule';
reason_include_once( 'minisite_templates/modules/default.php' );
reason_include_once( 'classes/object_cache.php' );
include_once(DISCO_INC.'disco.php');

class JensonMedalModule extends DefaultMinisiteModule {
		var $form;
		
		var $elements = array(
			'your_name' => 'text',
			'first_choice' => 'text',
			'second_choice' => 'text',
			'third_choice' => 'text',
		);
		
		var $custom_magic_transform_attributes = array('your_name');
		
		var $required = array('your_name', 'first_choice', 'second_choice', 'third_choice');
		
		function init( $args = array() ){
				force_secure();

				parent::init( $args );
				if ($head_items =& $this->get_head_items()) {
						$head_items->add_javascript('/reason/js/jenson_medal.js');
				}

				$this->form = new disco();
				$this->form->elements = $this->elements;
				$this->form->actions = array('Vote');
				$this->form->add_callback(array(&$this, 'run_error_checks'),'run_error_checks');
				$this->form->add_callback(array(&$this, 'on_every_time'),'on_every_time');
				$this->form->init();

				$url_parts = parse_url( get_current_url() );
		}
		
		function run_error_checks(){
				
		}
		
		function on_every_time(){
				$user = reason_require_authentication('voting_login_blurb');
				$user = 'adamem03';
				
				/*
				 * get data from db
				 */
				connectDB('jenson_medal_connection');
				$qstring = "SELECT * from `nominees` where `username` = '" . mysql_real_escape_string($user) . "' ";
				$results = db_query($qstring);
				$row = mysql_fetch_array($results, MYSQL_ASSOC);
				connectDB(REASON_DB);
				$formatted_name = $row['first_name'] . ' ' . $row['last_name'];
				/*
				 * if inelligible user accidentally gets here
				 */
				if (!$row) {
						echo '<div style="padding:30px">You are not eligible to vote. If you feel this is an error,
								please contact <a href="mailto:einckmic@luther.edu">Michelle Einck</a> in the Alumni Office, x1861.</div>';
						$this->form->show_form = false;
				}
				/*
				 * if user has already voted, display a message 
				 */
				if (!is_null($row['has_voted'])) {
						$this->form->show_form = false;
						echo '<div style="padding:30px">Logged in as: ' . $formatted_name . '</div>';
						echo '<div style="padding:30px">It appears that you\'ve already submitted your votes. If you feel this is an error,
								please contact <a href="mailto:einckmic@luther.edu">Michelle Einck</a> in the Alumni Office, x1861.</div>';
				} else { /* else, let them vote */
						$this->form->change_element_type('your_name', 'solidtext');
						$this->form->set_value('your_name', $formatted_name);
				}
				
				
				
				
								
				
		}
		
		function run(){
//				$this->display_form();
				$this->form->run();
		}
		
		function process(){
				echo 'booya!';
		}
}








?>

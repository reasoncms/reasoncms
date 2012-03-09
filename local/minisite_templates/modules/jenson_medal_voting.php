<?php
$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'JensonMedalModule';
reason_include_once( 'minisite_templates/modules/default.php' );
reason_include_once( 'classes/object_cache.php' );

// was told to include this DISCO_INC - burkaa
include_once(DISCO_INC.'disco.php');

class JensonMedalModule extends DefaultMinisiteModule {
		var $form;
		
		var $elements = array(
			'your_name' => 'text',
			'first_choice' => 'text',
			'second_choice' => 'text',
			'third_choice' => 'text',
		);
		
		var $required = array('your_name', 'first_choice', 'second_choice', 'third_choice');
		
		function init( $args = array() ){
				force_secure();
				
				parent::init( $args );
				if ($head_items =& get_head_items()) {
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
		
		function on_every_time(){
				$user = $this->reason_require_authentication();
				echo $user;
		}
		
		function run(){
				$this->display_form();
		}
}








?>

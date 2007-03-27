<?php

	reason_include_once( 'minisite_templates/modules/default.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'editorDemoModule';


	class editorDemoModule extends DefaultMinisiteModule
	{
		var $acceptable_params = array('demo' => array('function'=>'turn_into_string'));
		var $current_option = '';
		
		function init()
		{
			parent::init();
			
			// force secure form due to a bug that causes images not to work in unsecure environment
			if(!on_secure_page())
			{
				reason_include_once('function_libraries/user_functions.php');
				force_secure();
			}
		}
		
		function has_content() // {{{
		{
			return true;
		} // }}}
		
		function run() // {{{
		{
			echo '<div id="editorDemo">'."\n";
			$this->make_editor_form();
			echo '</div>';
		} // }}}
		
		function make_editor_form()
		{
			$form = new disco();
			$editor_name = html_editor_name($this->site_id);
			$params = html_editor_params($this->site_id);
			if(strpos($editor_name,'loki') === 0)
			{
				unset($params['paths']['site_feed']);
				unset($params['paths']['finder_feed']);
				unset($params['paths']['default_site_regexp']);
				unset($params['paths']['default_type_regexp']);
				$params['user_is_admin'] = true;
			}
			$form->add_element('demo',$editor_name,$params);
			$form->set_display_name('demo',' ');
			$form->run();
			
			if($form->get_value('demo'))
			{
				echo '<h3>Output</h3>'."\n";
				echo '<div class="echoBack">'."\n";
				echo $form->get_value('demo');
				echo '</div>'."\n";
				echo '<h3>Tidied Markup</h3>'."\n";
				echo '<div class="echoBack">'."\n";
				echo nl2br(htmlspecialchars($form->get_value('demo')));
				echo '</div>'."\n";
				echo '<h3>Raw Markup</h3>'."\n";
				echo '<div class="echoBack">'."\n";
				echo nl2br(htmlspecialchars($_POST['demo']));
				echo '</div>'."\n";
			}
		}
		
	}
?>

<?php
	reason_include_once( 'minisite_templates/modules/default.php' );
	reason_include_once( 'classes/error_handler.php');
	
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'steveModule';
	
	class steveModule extends PublicationModule
	{
		var $images = true;
		
		function init( $args = array() )
		{
			trigger_error('test');
			$es = new entity_selector($this->site_id);
			$es->add_type('image');
			$es->add_right_relationship( $this->page_id, relationship_id_of('minisite_page_to_image'));
			$result = $es->run_one();
			
			if ($result)
			{
				$this->images =& $result;
			}
		}
		
		function has_content()
		{
			return (!empty($this->images));
		}
		
		function run()
		{
			$count = count($this->images);
			echo '<div class="test">Hello World my name is Steve</p><p>The page has ' . $count . ' images attached.</div>'."\n";
			echo '<p>some .... that you want to echo</p>';
		}
	}
?>

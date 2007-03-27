<?php

	reason_include_once( 'minisite_templates/modules/content_base.php' );

	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'ContentFromModule';

	class ContentFromModule extends ContentModule
	{
		var $acceptable_params = array(
			// Should be the unique name of a Reason entity that has some kind of content
			'unique_name' => '',
		);
		
		function init( $args = array() )
		{
			$entity = new entity(id_of($this->params['unique_name']));
			$this->content = $entity->get_value( 'content' );
		}
	}
?>

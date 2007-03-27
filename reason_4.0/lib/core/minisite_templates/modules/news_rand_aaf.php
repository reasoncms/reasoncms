<?php
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'AAFRandNewsMinisiteModule';
	reason_include_once( 'minisite_templates/modules/news_rand.php' );

	class AAFRandNewsMinisiteModule extends RandNewsMinisiteModule
	{
		function show_back_link() // {{{
		{
			$post = get_entity_by_id( id_of( "aaf_profile_closing_text_blurb" ));
			echo $post[ 'content' ];
		} // }}}
	}
?>

<?php
$display_handler = 'image_display_handler';
$GLOBALS['display_name_handlers']['image.php3'] = 'image_display_handler';

if( !defined( 'DISPLAY_HANDLER_IMAGE_PHP3' ) )
{
	define( 'DISPLAY_HANDLER_IMAGE_PHP3',true );

	reason_include_once( 'classes/entity.php' );

	function image_display_handler( $id )
	{
		if( !is_object( $id ) )
			$e = new entity( $id );
		else $e = $id;
		
		$tn_name = $e->id().'_tn.'.$e->get_value( 'image_type' );
		$full_name = $e->id().'.'.$e->get_value( 'image_type' );
		if( file_exists( PHOTOSTOCK.$tn_name ) && (filesize(PHOTOSTOCK.$tn_name) > 0) )
			$image_name = $tn_name;
		elseif( file_exists( PHOTOSTOCK.$full_name ) && (filesize(PHOTOSTOCK.$full_name) > 0) )
			$image_name = $full_name;
		if( !empty($image_name) )
		{
			list( $width, $height ) = getimagesize( PHOTOSTOCK.$image_name );
			$mod_time = filemtime( PHOTOSTOCK.$image_name );
			return $e->get_value('name').'<br /><img src="'.WEB_PHOTOSTOCK.$image_name.'?cb='.$mod_time.'" width="'.$width.'" height="'.$height.'" alt="'.$e->get_value( 'description' ).'" />';
		}
		else
			return $e->get_value('name');
	}
}

?>

<?php
/**
 * Creates a quicktime link
 *
 * A link file is necessary to properly handle streaming quicktime files
 *
 * This script will create one for the requested media file id.
 *
 * @package reason
 * @subpackage scripts
 */

/**
 * include dependencies
 */
$reason_session = false;
include_once( 'reason_header.php' );
reason_include_once( 'classes/entity.php' );
reason_include_once( 'function_libraries/url_utils.php' );

$id = !empty( $_REQUEST[ 'id' ] ) ? $_REQUEST[ 'id' ] : '';
settype($id,'integer');

$db = connectDB(REASON_DB);
if( !empty( $id ) )
{
	$file = new entity( $id );
}
if( empty($id ) OR ($file->get_value( 'type' ) != id_of( 'av_file' )) OR ($file->get_value('state') != 'Live' ) )
{
	if(empty($id))
	{
		$xtra = 'Invalid id passed to script';
	}
	elseif($file->get_value( 'type' ) != id_of( 'av_file' ))
	{
		$xtra = 'id passed to script is not the id of a media file';
	}
	elseif($file->get_value('state') != 'Live' )
	{
		$xtra = 'media file requested is '.strtolower($file->get_value('state'));
	}
	if(!empty($_SERVER['HTTP_REFERER']))
	{
		$xtra .= ' ( Referrer: '.$_SERVER['HTTP_REFERER'].' )';
	}
	$file = null;
	trigger_error('Bad request on media link generator script - '.$xtra);
}
else
{
	$url = $file->get_value('url');
	if($file->get_value('reason_managed_media') && $file->get_value('default_media_delivery_method') != 'streaming')
	{
		$url = alter_protocol($url,'http','rtsp');
	}
	header('Content-Type: video/quicktime');
	echo '<?xml version="1.0"?>'."\n";
	echo '<?quicktime type="application/x-quicktime-media-link"?>'."\n";
	echo '<embed src="'.$url.'" />';
}

?>

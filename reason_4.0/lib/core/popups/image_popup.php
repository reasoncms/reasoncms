<?php
/**
 * This file creates the output of the image popup page
 * It can simply be included by the file that acts as the web-available "hook" and it should handle the rest
 * This file will use a separate template to produce the actual html markup
 * By default it will use the file at popup_templates/generic_image_popup_template.php
 * To replace the generic popup template with a custom one,
 * place a file in the popup_templates directory
 * and identify the template file by defining the constant IMAGE_POPUP_TEMPLATE_FILENAME 
 * in the Reason settings.
 * @author Matt Ryan
 * @package reason
 * @subpackage popup_templates
 */

	$reason_session = false;
	include_once( 'reason_header.php' );
	reason_include_once( 'function_libraries/images.php' );
	reason_include_once('function_libraries/image_tools.php');
	reason_include_once( 'function_libraries/file_finders.php' );
	reason_include_once( 'classes/entity.php' );
	
	$GLOBALS['_reason_image_popup_data'] = array();
	
	$id = !empty( $_REQUEST[ 'id' ] ) ? $_REQUEST[ 'id' ] : '';
	settype($id,'integer');

	$db = connectDB(REASON_DB);
	if( !empty( $id ) )
	{
		$image = new entity( $id );
	}
	if( empty($id ) OR !$image->get_values() OR ($image->get_value( 'type' ) != id_of( 'image' )) OR ($image->get_value('state') != 'Live' ) )
	{
		http_response_code(404);
		$title = 'No image found';
		if(!empty($_SERVER['HTTP_REFERER']) && !empty($id) ) // only trigger an error if there is a referer (e.g. we can do something about it)
		{
			if(!$image->get_values())
			{
				$xtra = 'id passed to script is not the id of a Reason entity';
			}
			elseif($image->get_value( 'type' ) != id_of( 'image' ))
			{
				$xtra = 'id passed to script is not the id of an image';
			}
			elseif($image->get_value('state') != 'Live' )
			{
				$xtra = 'image requested is '.strtolower($image->get_value('state'));
			}
			$xtra .= ' ( Referrer: '.$_SERVER['HTTP_REFERER'].' )';
			trigger_error('Bad image request on image popup script - '.$xtra);
		}
		$image = null;
	}
	
	if(!empty( $image ))
	{
		$GLOBALS['_reason_image_popup_data']['id'] = $id;
		$GLOBALS['_reason_image_popup_data']['title'] = $image->get_value( 'description' ) ? $image->get_value( 'description' ) : 'Image';
		$GLOBALS['_reason_image_popup_data']['image_exists'] = true;
		$GLOBALS['_reason_image_popup_data']['image_tag'] = '<img src="'.WEB_PHOTOSTOCK. reason_get_image_filename($id) .'" width="'.$image->get_value('width').'" height="'.$image->get_value('height').'" border="0" alt="'.htmlentities(strip_tags($image->get_value('description'))).'" />';
		
		$GLOBALS['_reason_image_popup_data']['image_caption'] = ($image->get_value('content') ? $image->get_value('content') : $image->get_value('description'));
		
		$GLOBALS['_reason_image_popup_data']['image_author'] = $image->get_value( 'author' );
	}
	else
	{
		$GLOBALS['_reason_image_popup_data']['image_exists'] = false;
		$GLOBALS['_reason_image_popup_data']['title'] = 'Image not found';
	}
	
	if(defined('IMAGE_POPUP_TEMPLATE_FILENAME'))
	{
		$template_path = 'popup_templates/'.IMAGE_POPUP_TEMPLATE_FILENAME;
	}
	else
	{
		$template_path = 'popup_templates/generic_image_popup_template.php';
	}
	
	if(reason_file_exists($template_path))
	{
		reason_include($template_path);
	}
	else
	{
		trigger_error('no template file found for the image popup script (looking for file at '.$template_path.'; change Reason setting IMAGE_POPUP_TEMPLATE_FILENAME to set the template file)');
	}
?>

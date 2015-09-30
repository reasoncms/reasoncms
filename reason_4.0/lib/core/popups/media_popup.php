<?php
/**
 * This file creates the output of the media popup page
 * It can simply be included by the file that acts as the web-available "hook" and it should handle the rest
 * This file will use a separate template to produce the actual html markup
 * By default it will use the file at popup_templates/generic_media_popup_template.php
 * To replace the generic popup template with a custom one,
 * place a file in the popup_templates directory
 * and identify the template file by defining the constant MEDIA_POPUP_TEMPLATE_FILENAME 
 * in the Reason settings.
 * @author Matt Ryan
 * @package reason
 * @subpackage popup_templates
 */
	if( empty( $_GET['id'] ) )
	{
		trigger_error('No ID passed to av display');
		die();
	}
	else
	{
		$id = $_GET['id'];
		settype($id,'integer');
		$GLOBALS['_reason_media_popup_data'] = array();
		$GLOBALS['_reason_media_popup_data']['id'] = $id;
		include_once('reason_header.php');
		reason_include_once( 'classes/entity.php' );
		reason_include_once( 'classes/av_display.php' );
		reason_include_once( 'function_libraries/file_finders.php' );
		
		$e = new entity( $id );
		if($e->get_value('type') != id_of('av_file'))
		{
			trigger_error('ID passed to av_display is not for a media file');
			die();
		}
		if($e->get_value('url'))
		{
			$avd = new reasonAVDisplay();
			$embed_markup = $avd->get_embedding_markup($id);
			if(empty($embed_markup))
			{
				$embed_markup = '<a href="'.$e->get_value('url').'">'.$e->get_value('url').'</a>';
			}
			else
			{
				$tech_note = $avd->get_tech_note($id);
				if(!empty($tech_note))
				{
					$embed_markup .= '<div class="techNote">'.$tech_note.'</div>'."\n";
				}
			}
		}
		else
		{
			$embed_markup = 'Please contact site maintainer for this file (No URL provided)';
		}
		$GLOBALS['_reason_media_popup_data']['embed_markup'] = $embed_markup;

		$rel = $e->get_right_relationship( 'av_to_av_file' );
		if( $rel )
		{
			reset( $rel );
			$rel = current( $rel );
			$GLOBALS['_reason_media_popup_data']['desc'] = $rel->get_value( 'description' );
			$GLOBALS['_reason_media_popup_data']['title'] = $rel->get_value( 'name' );
		}
		else
		{
			$GLOBALS['_reason_media_popup_data']['desc'] = '';
			$GLOBALS['_reason_media_popup_data']['title'] = $e->get_value( 'name' );
		}
		if(defined('MEDIA_POPUP_TEMPLATE_FILENAME'))
		{
			$template_path = 'popup_templates/'.MEDIA_POPUP_TEMPLATE_FILENAME;
		}
		else
		{
			$template_path = 'popup_templates/generic_media_popup_template.php';
		}
		if(reason_file_exists($template_path))
		{
			reason_include($template_path);
		}
		else
		{
			trigger_error('no template file found for the media popup script (looking for file at '.$template_path.'; change the Reason setting MEDIA_POPUP_TEMPLATE_FILENAME to specify the media popup tenmplate file)');
		}
	}
?>

<?php
reason_include_once( 'content_managers/media_file.php3');

$GLOBALS[ '_content_manager_class_names' ][basename(__FILE__) ] = 'lutherAvFileManager';

class lutherAvFileManager extends avFileManager
{
	function alter_data()
	{
		parent::alter_data();
		$this->add_comments('media_format', form_comment('Note: <img src="/reason/ui_images/youtube_16.jpg"/> videos are Flash Multimedia (.swf) format.'));
	}
}
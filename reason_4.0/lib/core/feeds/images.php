<?php
/**
 * @package reason
 * @subpackage feeds
 */

/**
 * Include dependencies & register feed with Reason
 */
include_once( 'reason_header.php' );
reason_include_once('function_libraries/image_tools.php');
reason_include_once( 'feeds/page_tree.php' );
$GLOBALS[ '_feed_class_names' ][ basename( __FILE__, '.php' ) ] = 'imagesFeed';

class imagesFeed extends defaultFeed
{
	var $feed_class = 'imagesRSS';

	function alter_feed()
	{
		// Start with defaults
 		$this->do_default_field_mapping();

		// Then change only the link field
		$this->feed->set_item_field_map('link', 'id');
		$this->feed->set_item_field_handler('link', 'make_link', true);
		$this->feed->set_item_field_map('enclosure', 'id');
		
		// Modify entity selector
		$num = !empty($_REQUEST['num']) ? turn_into_int($_REQUEST['num']) : '0';
		$start = !empty($_REQUEST['start']) ? turn_into_int($_REQUEST['start']) : '0';
		if( !empty($_REQUEST['q']) )
		{
			$this->feed->es->add_relation('(entity.name LIKE "%'.reason_sql_string_escape($_REQUEST['q']) . '%"' .
						      ' OR meta.description LIKE "%' . reason_sql_string_escape($_REQUEST['q']) . '%"'.
							  ' OR meta.keywords LIKE "%' . reason_sql_string_escape($_REQUEST['q']) . '%"'.
							  ' OR chunk.content LIKE "%' . reason_sql_string_escape($_REQUEST['q']) . '%"'.
							  ')');
		}
		$this->feed->es->set_num( $num );
		$this->feed->es->set_start( $start );
		$this->feed->es->set_order( 'entity.last_modified DESC, dated.datetime DESC, entity.name ASC' );
	}
}

class imagesRSS extends ReasonRSS
{
	// Revised to use Reason's image_tools as opposed to manually creating URLs for images
	function make_link($id)
	{
		$file_name = reason_get_image_filename($id);
		return 'http://'.REASON_HOST.WEB_PHOTOSTOCK.$file_name;
	}
	
	// Thumbnail
	function make_enclosure($item, $attr, $value)
	{
		static $mime_map = array(
			'art' => 'image/x-jg',
			'bmp' => 'image/x-ms-bmp',
			'gif' => 'image/gif',
			'ico' => 'image/vnd.microsoft.icon',
			'jpeg' => 'image/jpeg',
			'jpg' => 'image/jpeg',
			'jpe' => 'image/jpeg',
			'jfif' => 'image/jpeg',
			'jfi' => 'image/jpeg',
			'jif' => 'image/jpeg',
			'jp2' => 'image/jp2',
			'j2k' => 'image/jp2',
			'pict' => 'image/x-pict',
			'pct' => 'image/x-pict',
			'pcx' => 'image/x-pcx',
			'pic' => 'image/x-pict',
			'png' => 'image/png',
			'tif' => 'image/tiff',
			'tiff' => 'image/tiff'
		);
		
		$filename = reason_get_image_filename($value, 'thumbnail');
		$url = 'http://'.REASON_HOST.WEB_PHOTOSTOCK.$filename;
		$type = 'image/x-unknown';
		if(!empty($extension) && array_key_exists($extension, $mime_map))
		{
			$type = $mime_map[$extension];
		}
		
		$size = (file_exists(PHOTOSTOCK.$filename)) ? filesize(PHOTOSTOCK.$filename) : 0;
	
		return '<'.$attr.' url="'.$url.'" length="'.$size.'" type="'.$type.'" />'."\n";
	}
}

?>

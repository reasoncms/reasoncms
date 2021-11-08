<?php

reason_include_once( 'entity_delegates/abstract.php' );

$GLOBALS['entity_delegates']['entity_delegates/page.php'] = 'pageDelegate';

/**
 * @todo implement methods that help with ingestion of images
 */
class pageDelegate extends entityDelegate
{
	function get_url($type = '')
	{
		return reason_get_page_url( $this->entity );
	}
	function get_export_generated_data()
	{
		$ret = array();
		$ret['url'] = $this->entity->get_url();
		return $ret;
	}
}
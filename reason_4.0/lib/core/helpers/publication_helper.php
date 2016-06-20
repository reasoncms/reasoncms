<?php
/**
 * @package reason
 * @subpackage classes
 * @author Nathan White
 */

include_once( 'reason_header.php' );
reason_include_once( 'classes/entity.php' );
	
/**
 * The publication helper provides useful extra methods for publication entities 
 */

class PublicationHelper extends entity
{
	/**
	 * Force the inclusion of the publication delegate
	 */
	function get_delegates()
	{
		$delegates = parent::get_delegates();
		if(!isset($delegates['entity_delegates/publication.php']))
			$this->add_delegate('entity_delegates/publication.php', new publicationDelegate($this->id() );
		return parent::get_delegates();
	}
}
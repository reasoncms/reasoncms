<?php
/**
 * Helper class for "borrow this" functionality
 * @package reason
 * @subpackage classes
 */
 
/**
 * Helper class for "borrow this" functionality
 */
class BorrowThis {
	/**
	 * Get a link to the admin borrowThis module
	 *
	 * @param mixed $item Entity id or object
	 * @return string link
	 */
	static function link($item, $return_to = true) {
		if(is_object($item))
			$item = $item->id();
			
		$query_array = array(
			'cur_module' => 'BorrowThis',
			'borrow_id' => $item
		);
		if($return_to)
		{
			if(true == $return_to)
				$query_array['return_to'] = get_current_url();
			else
				$query_array['return_to'] = $return_to;
		}
		return securest_available_protocol() . '://' . REASON_WEB_ADMIN_PATH . carl_construct_query_string($query_array);
	}
	/**
	 * Is a given entity generally borrowable?
	 *
	 * Checks entity and site's sharing settings
	 *
	 * @param mixed $item Entity id or object
	 * @return boolean
	 */
	static function borrowable($item)
	{
		if(is_integer($item))
			$item = new entity( (integer) $item );
			
		if($item->get_values())
		{
			$no_share = (integer) $item->get_value('no_share');
			if($no_share)
				return false;
			$site_id = get_owner_site_id( $item->id() );
			if(empty($site_id))
				return false;
			return site_shares_type($site_id, $item->get_value('type'));
		}
		return false;
	}
	/**
	 * Can a given username borrow a given entity?
	 *
	 * Checks to see if:
	 * 	a) username corresponds to a reason site editor for at least one site
	 * 	b) that user has borrowing privileges
	 * 	c) the entity is generally borrowable
	 *
	 * @param mixed $item Entity id or object
	 * @param mixed $username username string or NULL for current user
	 * @return boolean
	 */
	static function item_borrowable_by_username($item, $username = NULL)
	{
		if(empty($username))
			$username = reason_check_authentication();
		if($username)
		{
			$id = get_user_id($username);
			return ($id && user_is_a_reason_editor($id) && reason_user_has_privs($id, 'borrow') && BorrowThis::borrowable($item));
		}
		return false;
	}
}
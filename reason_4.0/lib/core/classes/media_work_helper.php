<?php
	/**
	 * The media work helper
	 * @package reason
	 * @subpackage classes
	 */

	/**
	 * Include reason libraries
	 */ 
	include_once( 'reason_header.php' );
	reason_include_once('classes/group_helper.php');
	
	/** 
	* This class wraps up a media work entity and provides useful function for it.  (At the moment, checking access restrictions is the only functionality.)
	*
	* Sample usage:
	*
	* <code>
	* $mwh = new media_work_helper($media_work_entity);
	* if($mwh->user_has_access_to_media($username))
	* {
	* 	echo $username.' has access to the media_work.';
	* }
	* </code>
	*
	* Created 2012-01-17
	* @author Marcus Huderle
	*/
	class media_work_helper
	{
		/**
		 * The media work entity that this class wraps up
		 *
		 * Use either set_media_work_by_entity() or set_media_work_by_id() to set this value
		 * @access private
		 * @var object
		 */
		var $media_work;
		
		/**
		* Constructor.  You must pass in either a media work entity or id.
		*
		* @param entity or id
		* @access public
		*/
		function __construct($media_work)
		{
			if ( !$this->set_media_work($media_work) )
			{
				trigger_error("media_work_helper requires a media work entity or id in its constructor.", HIGH);
			}
		}
		
		/**
		 * Initializes media work helper by setting the media work id to wrap up
		 * @param entity or int of media work
		 * @access public
		 * @return boolean success
		 */
		function set_media_work($media_work)
		{
			if(is_object($media_work))
			{
				$this->media_work = $media_work;
			}
			else if (is_numeric($media_work) && !empty($media_work))
			{
				$this->media_work = new entity($media_work);
			}
			else
			{
				return false;
			}
			return true;
		}
		
		/**
		* Determines whether or not the current user has access to the specified media work.  If no username is provided, this function defaults to the currently-loggin-in username.
		*
		* @param string $username
		* @return boolean user has access
		*/
		public function user_has_access_to_media($username = '')
		{
			// First, get the restricted group--if one exists
			$es = new entity_selector();
			$es->add_type(id_of('group_type'));
			$es->add_right_relationship($this->media_work->id(), relationship_id_of('av_restricted_to_group'));
			$group = current($es->run_one());
			
			if (!empty($group))
			{
				$gh = new group_helper();
				$gh->set_group_by_id($group->id());
				if ( $gh->requires_login() ) 
				{
					if ( !$username )
					{
						$username = reason_check_authentication();
					}
					if ($username)
					{
						if (!$gh->is_username_member_of_group($username))
						{
							return false;
						}
					}
					else
					{
						return false;
					}
				}
			}
			return true;  // Return true if the user has access to view media work
		}
		
	}	
?>	
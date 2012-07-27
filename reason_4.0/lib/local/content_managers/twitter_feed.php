<?php
/**
 * @package reason
 * @subpackage content_managers
 */
	/**
	 * Register content manager with Reason
	 */
	$GLOBALS[ '_content_manager_class_names' ][ basename( __FILE__) ] = 'LutherTwitterFeed';

	/**
	 * A content manager for text blurbs
	 */
	class LutherTwitterFeed extends ContentManager
	{
		function alter_data()
		{
			$this->set_display_name('name', 'Twitter Username');
			$this->set_comments('name', form_comment('Enter username used to access Twitter account.'));
			$this->set_display_name('twitter_posts', 'Recent Posts?');
			$this->set_comments('twitter_posts', form_comment('How many recent posts should rotate through the scroll.
				<br />Enter 0 for a random display of all posts.'));
			$this->add_required('twitter_posts');
			
			$this->set_order(
				array(
					'name',
					'twitter_username',
					'twitter_posts',
				)
		);
		}
	}
?>

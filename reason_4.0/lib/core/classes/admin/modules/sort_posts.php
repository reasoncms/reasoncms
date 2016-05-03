<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module and other needed utilities
  */
	reason_include_once('classes/admin/modules/default.php');
	include_once( DISCO_INC . 'disco.php');
	
	/**
	 * Simple Sorter for Posts
	 *
	 * @author Matt Ryan
	 */
	
	class SortPostsModule extends DefaultModule // {{{
	{
		var $thor_viewer; // thor viewer object
		var $form; // form entity
		var $issue;
		var $posts;
		var $locked_posts = array();
		
		function SortPostsModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		
		/**
		 * Standard Module init function
		 *
		 * @return void
		 */
		function init()
		{
			parent::init();
			if(!empty($this->admin_page->id))
			{
				$this->issue = new entity( $this->admin_page->id );
			}
			if(empty($this->issue) || $this->issue->get_value('type') != id_of('issue_type'))
			{
				trigger_error('Sort Posts module run on a non-issue entity',EMERGENCY);
				die();
			}
			$this->admin_page->title = 'Sort Posts on issue "' . $this->issue->get_value('name').'"';
			
			$es = new entity_selector( $this->admin_page->site_id);
			$es->add_type(id_of('news'));
			$es->set_sharing( 'owns' );
			$es->add_left_relationship($this->issue->id(), relationship_id_of('news_to_issue'));
			$es->set_order('dated.datetime DESC');
			$this->posts = $es->run_one();
			
			$user = new entity($this->admin_page->user_id);
			foreach($this->posts as $id => $post)
			{
				if(!$post->user_can_edit_field('datetime',$user))
				{
					$this->locked_posts[$id] = $post;
				}
			}
			
		}
		
		/**
		 * @return void
		 */
		function run() // {{{
		{
			$d = new sortPostsDisco();
			$d->set_posts($this->posts);
			$d->set_locked_posts($this->locked_posts);
			$d->set_user_id($this->admin_page->user_id);
			echo '<div class="sortPostsModule">'."\n";
			$d->run();
			echo '</div>'."\n";
		}
		
	} // }}}
	
	class sortPostsDisco extends disco
	{
		var $posts = array();
		var $locked_posts = array();
		var $user_id;
		var $changes_made = false;
		var $box_class = 'StackedBox';
		
		function set_posts($posts)
		{
			$this->posts = $posts;
			foreach($this->posts as $id=>$post)
			{
				$this->add_element($id, 'textDateTime');
				$this->set_value($id, $post->get_value('datetime'));
				$this->set_display_name($id, $post->get_value('name'));
			}
		}
		function set_locked_posts($posts)
		{
			$this->locked_posts = $posts;
			foreach($this->locked_posts as $id=>$post)
			{
				if($this->is_element($id))
				{
					$this->change_element_type($id, 'solidtext');
					$this->set_comments($id, '<img 	class="lockIndicator" src="'.REASON_HTTP_BASE_PATH.'ui_images/lock_12px.png" alt="locked" width="12" height="12" />', 'before' );
				}
			}
		}
		function set_user_id($user_id)
		{
			$this->user_id = $user_id;
		}
		function process() // {{{
		{
			foreach($this->posts as $id=>$post)
			{
				if($this->get_value($id) && $this->get_value($id) != $post->get_value('datetime') && !isset($this->locked_posts[$id]) )
				{
					reason_update_entity( $id, $this->user_id, array('datetime'=>$this->get_value($id)));
					$this->changes_made = true;
				}
			}
		} // }}}
		function pre_show_form() // {{{
		{
			if($this->changes_made)
			{
				echo '<h3>Changes saved</h3>'."\n";
			}
			echo '<p class="smallText"><a href="'.carl_make_link( array('cur_module'=>'Editor') ).'">Done sorting posts</a></p>'."\n";
		} // }}}
	}
?>
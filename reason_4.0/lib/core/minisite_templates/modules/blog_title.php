<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 	/**
 	 * Include the base class & register module with Reason
 	 */
	reason_include_once( 'minisite_templates/modules/page_title.php' );
	
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'blogTitleModule';
	
	/**
	 * A minisite module that displays the title of the blog attached to the page
	 */
	class blogTitleModule extends PageTitleModule
	{
		var $blog;
		var $cleanup_rules = array(
			'story_id' => array( 'function' => 'turn_into_int' ),
		);
		function init( $args = array() )
		{
			$es = new entity_selector( $this->parent->site_id );
			$es->description = 'Selecting blogs/publications for this page';
			$es->add_type( id_of('publication_type') );
			$es->add_right_relationship( $this->parent->cur_page->id(), relationship_id_of('page_to_publication') );
			$es->set_num( 1 );
			$blogs = $es->run_one();
			if(!empty($blogs))
			{
				$this->blog = current($blogs);
			}
		}
		function has_content()
		{
			if( !empty($this->blog) && $this->blog->get_value('name') )
				return true;
			elseif( !empty( $this->parent->title ) )
				return true;
			else
				return false;
		}
		function run()
		{
			if( !empty($this->blog) && $this->blog->get_value('name') )
			{
				if(!empty($this->request['story_id']))
				{
					if($this->textonly)
					{
						$link = '?textonly=1';
					}
					else
					{
						$link = '?';
					}
					$blogname = '<a href="'.$link.'">'.$this->blog->get_value('name').'</a>';
				}
				else
				{
					$blogname = $this->blog->get_value('name');
				}
				echo '<h2 class="pageTitle"><span>'.$blogname.'</span></h2>';
			}
			elseif( !empty( $this->parent->title ) )
				parent::run();
		}
	}
?>

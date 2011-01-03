<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 	/**
 	 * Include parent class and register module with Reason
 	 */
    $GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'NewsProofingModule';
    reason_include_once( 'minisite_templates/modules/news_all.php' );
    
    /**
     * @deprecated
     */
    class NewsProofingNoIssueViewer extends AllNewsNoIssueViewer
    {
		function show_item( &$item, $options = false ) 
        {              
            echo "<h3 class='newsTitle'>".strip_tags( $item->get_value( 'release_title' ) )."</h3>\n";
            
            if( $item->get_value( 'datetime' ) )
				echo '<p class="smallText newsDate">'.prettify_mysql_datetime( $item->get_value( 'datetime' ), "F jS, Y" )."</p>\n";
            if( $item->get_value( 'author' ) )
				echo "<p class='smallText newsAuthor'>By ".$item->get_value( 'author' )."</p>\n";
            
            $owner = $item->get_owner();			
            
            if ( $owner->get_value('id') != $this->site_id )
            {
                echo '<p class="smallText newsProvider">This story is provided by ';
                $base_url = $owner->get_value('base_url');
                if (!empty($base_url))
                        echo '<a href="'. $base_url . '">'. $owner->get_value('name') . '</a>';
                else echo $owner->get_value('name');
                echo "</p>\n";
            }
        
			echo '<h4>Description</h4>'."\n";
			echo $item->get_value( 'description' );
			
            if ( $item->get_value( 'content' ) )
			{
				echo '<h4>Content</h4>'."\n";
				echo $item->get_value( 'content' );
			}
        }
    }

	/**
     * @deprecated
     */
    class NewsProofingIssueViewer extends AllNewsIssueViewer
    {
		function show_item( &$item, $options = false ) 
        {              
            echo "<h3 class='newsTitle'>".strip_tags( $item->get_value( 'release_title' ) )."</h3>\n";
            
            if( $item->get_value( 'datetime' ) )
				echo '<p class="smallText newsDate">'.prettify_mysql_datetime( $item->get_value( 'datetime' ), "F jS, Y" )."</p>\n";
            if( $item->get_value( 'author' ) )
				echo "<p class='smallText newsAuthor'>By ".$item->get_value( 'author' )."</p>\n";
            
            $owner = $item->get_owner();			
            
            if ( $owner->get_value('id') != $this->site_id )
            {
                echo '<p class="smallText newsProvider">This story is provided by ';
                $base_url = $owner->get_value('base_url');
                if (!empty($base_url))
                        echo '<a href="'. $base_url . '">'. $owner->get_value('name') . '</a>';
                else echo $owner->get_value('name');
                echo "</p>\n";
            }
        
			echo '<h4>Description</h4>'."\n";
			echo $item->get_value( 'description' );
			
            if ( $item->get_value( 'content' ) )
			{
				echo '<h4>Content</h4>'."\n";
				echo $item->get_value( 'content' );
			}
        }
        
        function show_owner( $item, $site_id ) 
		{
		} 
    }
	
	/**
	 * A minisite template that shows, in one big page, all the stories' descriptions and content
	 *
	 * Note: this module is deprecated. Use the publication framework instead. If you want this behavior, create a custom
	 * list_item_markup_generator that shows the content of the stories.
	 *
	 * @deprecated
	 */
	class NewsProofingModule extends AllNewsModule
    {
		var $limit_to_shown_issues = false;
        
        function list_news_issue() 
		{
            $this->show_issue_options();
            $v = new NewsProofingIssueViewer;
            $v->current_issue =& $this->current_issue;
            $v->num_per_page = 500000;
            $v->request = &$this->request;
            $v->init( $this->parent->site_id , id_of( 'news' ) );
            $v->textonly = $this->parent->textonly;
            $v->do_display();
		} 
        
        function list_news_no_issue() 
        {
            $v = new NewsProofingNoIssueViewer;
            $v->num_per_page = $this->num_per_page;
            $v->request = &$this->request;
            $v->init( $this->parent->site_id , id_of( 'news' ) );
            $v->textonly = $this->parent->textonly;
            $v->do_display();
        }
        function show_story()
        {
        	$story = new entity( $this->request[ 'story_id' ] );
            echo "<h3 class='newsTitle'>".strip_tags( $story->get_value( 'release_title' ) )."</h3>\n";
            
            if( $story->get_value( 'datetime' ) )
				echo '<p class="smallText newsDate">'.prettify_mysql_datetime( $story->get_value( 'datetime' ), "F jS, Y" )."</p>\n";
            if( $story->get_value( 'author' ) )
				echo "<p class='smallText newsAuthor'>By ".$story->get_value( 'author' )."</p>\n";
            
           $this->show_owner( $story );
        
			echo '<h4>Description</h4>'."\n";
			echo $story->get_value( 'description' );
			
            if ( $story->get_value( 'content' ) )
			{
				echo '<h4>Content</h4>'."\n";
				echo $story->get_value( 'content' );
			}

        }
        
        function show_owner( $e )  // {{{
		{
            $owner = $e->get_owner();			
            
#            if ( $owner->get_value('id') != $this->parent->site_id )
            if ( $owner->get_value('id') != $this->site_id )
            {
                echo '<p class="smallText newsProvider">This story is provided by ';
                $base_url = $owner->get_value('base_url');
                if (!empty($base_url))
                        echo '<a href="'. $base_url . '">'. $owner->get_value('name') . '</a>';
                else echo $owner->get_value('name');
                echo "</p>\n";
            }
		} // }}}
    }

    
?>
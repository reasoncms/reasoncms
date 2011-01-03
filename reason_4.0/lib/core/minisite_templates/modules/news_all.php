<?php
/**
 * A module that displays the full news items in a list for easy printing, etc.
 *
 * @deprecated
 *
 * @package reason
 * @subpackage minisite_modules
 */
 
 /**
  * Store the class name so the template can use this module
  */
    $GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'AllNewsModule';
    
 /**
  * Include dependencies
  */
    reason_include_once( 'minisite_templates/modules/news.php' );
    
    /**
     * Class for showing items if no issues are present on all_news module
     *
     * This class is an artifact of the old news framework.
     *
     * @todo Refactor this class out of existence
     */
    class AllNewsNoIssueViewer extends no_issue_news_viewer
    {
        function show_item( &$item, $options = false )  // {{{
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
            
			if ( $item->get_value( 'content' ) )
				echo str_replace(array('<h3>','</h3>'), array('<h4>','</h4>'), $item->get_value( 'content' ));
            else
				echo $item->get_value( 'description' );
		}
	}
	
	 /**
     * Class for showing items if issues are present on all_news module
     *
     * This class is an artifact of the old news framework.
     *
     * @todo Refactor this class out of existence
     */
    class AllNewsIssueViewer extends issue_news_viewer
    {
        function show_item( &$item, $options = false )  // {{{
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
            
			if ( $item->get_value( 'content' ) )
				echo str_replace(array('<h3>','</h3>'), array('<h4>','</h4>'), $item->get_value( 'content' ));
            else
				echo $item->get_value( 'description' );
        } // }}}
        function show_owner( $item, $site_id )  // {{{
		{
            AllNewsModule::show_owner( $item );
		}  // }}}
    }

    /**
	 * A module that displays the full news items in a list for easy printing, etc.
	 *
	 * NOTE: This is a convenience class that will probably not last much longer -- it uses really horrendous badness and is not publication-aware, so it will list *all* posts in the current site, regardless of publication. However, it is still needed because we do not natively support this functionality in the publication module
	 *
	 * Here's how one would replicate this functionality in the publications framework: 1. make a list item markup generator that shows the full content, etc. rather than the description; 2. make a page type that sets num_per_page to a really high number; 3. associate the appropriate publication with the page assigned the page type (it might be best to do it using the related publication approach, so that there is still a single "official" place where the publication exists.)
	 *
	 * @todo Make sure this functionality is built into the publication framework so we can deprecate this code. Use the approach outlined in the full description of this module.
	 *
	 */
    class AllNewsModule extends NewsMinisiteModule
    {
        function run()  // {{{
        {
            if( !empty( $this->request[ 'story_id' ] ) )
            {
                ob_start();
                $this->show_story();
            }
            else
            {
                $this->list_news();
            }
        }  // }}}
        function list_news_issue()  // {{{
		{
            $this->show_issue_options();
            $v = new AllNewsIssueViewer;
            $v->current_issue =& $this->current_issue;
            $v->num_per_page = 500000;
            $v->request = &$this->request;
#            $v->init( $this->parent->site_id , id_of( 'news' ) );
            $v->init( $this->site_id , id_of( 'news' ) );
#            $v->textonly = $this->parent->textonly;
            $v->textonly = $this->textonly;
            $v->do_display();
		}  // }}}
        function list_news_no_issue()  // {{{
        {
            $v = new AllNewsNoIssueViewer;
            $v->num_per_page = $this->num_per_page;
            $v->request = &$this->request;
#            $v->init( $this->parent->site_id , id_of( 'news' ) );
			$v->init( $this->site_id , id_of( 'news' ) );
#            $v->textonly = $this->parent->textonly;
 		 	$v->textonly = $this->textonly;
            $v->do_display();
        }  // }}}
        function show_story() // {{{
        {
        	$story = new entity( $this->request[ 'story_id' ] );
            echo "<h3 class='newsTitle'>".strip_tags( $story->get_value( 'release_title' ) )."</h3>\n";
            
            if( $story->get_value( 'datetime' ) )
				echo '<p class="smallText newsDate">'.prettify_mysql_datetime( $story->get_value( 'datetime' ), "F jS, Y" )."</p>\n";
            if( $story->get_value( 'author' ) )
				echo "<p class='smallText newsAuthor'>By ".$story->get_value( 'author' )."</p>\n";
            
            AllNewsModule::show_owner( $story );
            
			if ( $story->get_value( 'content' ) )
				echo str_replace(array('<h3>','</h3>'), array('<h4>','</h4>'), $story->get_value( 'content' ));
            else
				echo $story->get_value( 'description' );
        } // }}}
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

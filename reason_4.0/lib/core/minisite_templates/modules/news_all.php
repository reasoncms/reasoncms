<?php
    $GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'AllNewsModule';
    reason_include_once( 'minisite_templates/modules/news.php' );
    
    class AllNewsNoIssueViewer extends no_issue_news_viewer
    {
        function show_item( $item )  // {{{
        {              
            AllNewsModule::show_story($item);
        }  // }}}
    }

    class AllNewsIssueViewer extends issue_news_viewer
    {
        function show_item( $item )  // {{{
        {
            AllNewsModule::show_story($item);
        } // }}}
        function show_owner( $item, $site_id )  // {{{
		{
            AllNewsModule::show_owner( $item );
		}  // }}}
    }

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
        function show_story( $story ) // {{{
        {
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

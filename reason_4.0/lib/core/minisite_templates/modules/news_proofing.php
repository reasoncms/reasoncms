<?php
    $GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'NewsProofingModule';
    reason_include_once( 'minisite_templates/modules/news_all.php' );
        
    class NewsProofingNoIssueViewer extends AllNewsNoIssueViewer
    {
		function show_item( $item ) 
        {              
            NewsProofingModule::show_story($item);
        }
    }

    class NewsProofingIssueViewer extends AllNewsIssueViewer
    {
        function show_item( $item ) 
        {
            NewsProofingModule::show_story($item);
        }
        
        function show_owner( $item ) 
		{
		} 
    }
	
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
        function show_story( $story )
        {
            echo "<h3 class='newsTitle'>".strip_tags( $story->get_value( 'release_title' ) )."</h3>\n";
            
            if( $story->get_value( 'datetime' ) )
				echo '<p class="smallText newsDate">'.prettify_mysql_datetime( $story->get_value( 'datetime' ), "F jS, Y" )."</p>\n";
            if( $story->get_value( 'author' ) )
				echo "<p class='smallText newsAuthor'>By ".$story->get_value( 'author' )."</p>\n";
            
            NewsProofingModule::show_owner( $story );
        
			echo '<h4>Description</h4>'."\n";
			echo $story->get_value( 'description' );
			
            if ( $story->get_value( 'content' ) )
			{
				echo '<h4>Content</h4>'."\n";
				echo $story->get_value( 'content' );
			}

        }
    }

    
?>
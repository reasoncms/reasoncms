<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
	/**
	 * Register module with Reason and include dependencies
	 */
	reason_include_once( 'minisite_templates/modules/policy_related.php' );
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'AllRelatedPolicyModule';

	/**
	 * A minisite module that displays the full tree of policies attached to the current page
	 */
	class AllRelatedPolicyModule extends RelatedPolicyModule
	{
		function init( $args = array() ) // {{{
		{
			parent::init( $args );
                        /*
			$es = new entity_selector( $this->parent->site_id );
			$es->add_type( id_of( 'policy_type' ) );
			//$es->set_order( 'sortable.sort_order ASC' );
			$es->set_order( 'entity.name ASC' );
			$es->add_left_relationship_field( 'parent' , 'entity' , 'id' , 'parent_id' );
			$es->add_right_relationship( $this->parent->cur_page->id(), relationship_id_of('page_to_policy') );
                        
			$this->values = $es->run_one();
                        */
                        //$this->pages->order_by = 'sortable.sort_order ASC';
                        
                        //Set up our AllPolicyNavigation to grab all the policies associated
                        //with a given page and sort them by sort_order. 
                        //I am not really sure how necessary it is to use a mutiple_root_tree_viewer,
                        //but it works. 
			$this->pages = new AllPolicyNavigation;
			$this->pages->init( $this->parent->site_id, id_of('policy_type') );
			$this->pages->es->set_order( 'sortable.sort_order ASC' );
			$this->pages->es->add_left_relationship_field( 'policy_parent' , 'entity' , 'id' , 'parent_id' );
			$this->pages->es->add_right_relationship( $this->parent->cur_page->id(), relationship_id_of('page_to_policy') );
                        $this->pages->values = $this->pages->es->run_one();
                        
                        //$this->pages->request =& $this->request;
                        
                        //I don't think this kludge is required for this usage, so I am commenting it out
                        //Just in case, though, I am leaving it in the code. JLO 03/10/04
			// small kludge - just give the tree view access to the site info.  used in the show_item function to show the root node of the navigation
			//if ( !empty ( $this->site_info ) )
			//	$this->pages->site_info = $this->site_info;
		} // }}}
                
                function run()
                {
                    if( !empty( $this->request[ 'policy_id' ] ) )
                    {
                        $policy = new entity( $this->request[ 'policy_id' ] );
                        $this->pages->show_item( $policy );
                    }
                    else
                    {
                        $this->get_root_nodes();
                        $this->pages->roots = $this->roots;
                        $this->pages->show_all_items();
                    }
                }
	}
	
	class AllPolicyNavigation extends PolicyNavigation
	{
            var $roots;
                
		function show_all_items() // {{{
		{
                        //pray( $this );
			foreach($this->values as $item)
			{				
				
				//$this->make_tree( $root , $root , 0);
                                //$item = new entity( $root );
                                $this->show_item( $item );
                                /*echo '<div class="policy">'."\n";
				$policy_author = $item->get_value( 'author' );
				$policy_date = prettify_mysql_datetime($item->get_value( 'datetime' ), "F j, Y");
				if (!empty($policy_author) || (!empty($policy_date)))
				{
                                        echo '<p class="policyAdopted">Adopted ';
					if (!empty($policy_author))
					{
						echo " by " . $policy_author;
					}
					if (!empty($policy_date))
					{
						echo " on " . $policy_date;
					}
					echo ".</p>\n";
				}
				echo '</div>'."\n";*/
			}
		} // }}} 
	}
?>

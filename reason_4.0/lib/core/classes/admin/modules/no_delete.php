<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module
  */
	reason_include_once('classes/admin/modules/default.php');
	
	/**
	 * An administrative module that explains why a given item may not be deleted
	 */
	class NoDeleteModule extends DefaultModule // {{{
	{
		function NoDeleteModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}		
		function init() // {{{
		{
			$dbq = $this->admin_page->get_required_ar_dbq();
			
			$this->values = $dbq->run();
			
			$this->borrowed_by = get_sites_that_are_borrowing_entity($this->admin_page->id);
			
			/* if( empty( $this->values ) && empty($this->borrowed_by ) )
			{
				$link = unhtmlentities( $this->admin_page->make_link( array( 'cur_module' => 'Delete' ) ) );
				header( 'Location: ' . $link );
				die();
			} */
			$this->admin_page->title = 'Why can\'t I delete this item?';
		} // }}}
		function is_root_node() // {{{
		{
			static $first = true;
			static $val = false;

			if( $first )
			{
				$first = false;
				foreach( $this->values AS $value )
				{
					if( $value[ 'entity_a' ] == $value[ 'entity_b' ] )
						$val = true;
				}
			}
			return $val;
		} // }}}
		function run() // {{{
		{
			$type = new entity( $this->admin_page->type_id );
			$entity = new entity($this->admin_page->id);
			$user = new entity( $this->admin_page->user_id );
			$text = 
			array
			( 
							'root_node' => 'This is a root ' . $type->get_value( 'name' ) . ', so you may not delete it.  If you wish to delete this item, please contact the <a href="' . $this->admin_page->make_link(array("cur_module"=>"about_reason")) . '">web team</a>.',
							'default' => 'You cannot currently delete this item because following items, which 
										are associated with it, must be associated with a '.$type->get_value( 'name' ).'. If 
										you wish to delete this item, you must first select a different '.$type->get_value( 'name' ).' for each of the following items.<br /><br />',
							id_of( 'minisite_page' ) => 'This page has children.  In order to delete it, you must first either:
										<ul>
										<li>delete its children</li>
										<li>Select a different parent page for its children</li>
										</ul>If you wish to delete this item, please select a different parent for the pages listed below.<br /><br />',
							'borrowed' => '<p>This item is currently borrowed by one or more sites.  Deleting it might break their sites.  If you still want to delete it, contact the sites\' maintainers to ask if they can stop borrowing the item.</p>',
							'locks' => 'This '.$type->get_value( 'name' ).' has had a lock applied to it that keeps it from being deleted. A reason administrator may have applied this lock in order to ensure that a site was not inadventently broken. Please contact a Reason administrator if you have any questions about the rationale for placing this lock on this '.$type->get_value( 'name' ).'.',
			);
			if(!empty($this->borrowed_by))
			{
				echo $text['borrowed'];
				echo '<h4>Sites borrowing this item</h4>'."\n";
				echo '<ul>'."\n";
				foreach($this->borrowed_by as $site)
				{
					echo '<li><a href="'.$site->get_value('base_url').'">'.$site->get_value('name').'</a>'."\n";
					echo '<div>Primary maintainer: '.$site->get_value('name_cache').', <a href="mailto:'.$site->get_value('email_cache').'" title="send email">'.$site->get_value('email_cache').'</a></div></li>'."\n";
				}
				echo '</ul>'."\n";
			}
			elseif( $this->is_root_node() )
			{
				echo $text[ 'root_node' ];
			}
			elseif(!$entity->user_can_edit_field('state',$user))
			{
				echo $text[ 'locks' ];
			}
			else
			{
				if( !empty( $text[ $this->admin_page->type_id ] ) )
					echo $text[ $this->admin_page->type_id ];
				else
					echo $text[ 'default' ];

				foreach( $this->values AS $v )
				{
					$link = $this->admin_page->make_link( array( 'cur_module' => 'Preview',
																 'id' => $v[ 'e_id' ],
																 'type_id' => $v[ 'relationship_a' ],
																)
														);

					echo '<a href="' . $link . '" target="'.$v[ 'e_id' ].'">' . $v[ 'e_name' ] . 
						 '</a><span class="smallText"> ('.prettify_string( $v[ 'name' ] ).')</span><br />';
																 
				}
			}

		} // }}}
	} // }}}
?>
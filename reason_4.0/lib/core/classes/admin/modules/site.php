<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module and other needed utilities
  */
	reason_include_once('classes/admin/modules/default.php');
	//reason_include_once( 'function_libraries/images.php' );
	
	/**
	 * The administrative module that greets users when they select a site
	 *
	 * This module lists recently edited entities, as well as basic info like
	 * a notice if the site isn't live, adn the site's description if it has one.
	 */
	class SiteModule extends DefaultModule // {{{
	{
		function SiteModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		function init() // {{{
		{
			$sites = $this->admin_page->get_sites();
			if( count( $sites ) == 1 )
				parent::init();
			$this->admin_page->title = $this->admin_page->get_name( $this->admin_page->site_id );

			$this->admin_page->set_breadcrumbs(
				array( 
					$this->admin_page->make_link( array( 'site_id' => $this->admin_page->site_id, 'type_id' => '', 'id' => '' ) ) => $this->admin_page->get_name( $this->admin_page->site_id ),
				)
			);
		} // }}}
		function run() // {{{
		{
			echo '<div id="siteIntro">'."\n";
			$e = new entity( $this->admin_page->site_id );
			echo '<div id="siteNotices">'."\n";
			if( $e->get_value('site_state') == "Not Live" && $e->get_value('unique_name') != 'master_admin' )
			{
				echo '<div class="notLiveNotice"><h4>This site is not live.</h4><p>Among other things, that means that it\'s excluded from search engines (so people won\'t stumble upon a site that isn\'t ready for public consumption).</p>'."\n";
				if( user_can_edit_site($this->admin_page->user_id, id_of('master_admin') ) )
					echo '<p><a href="'.$this->admin_page->make_link( array('site_id'=>id_of('master_admin'),'type_id'=>id_of('site'),'id'=>$e->id(),'cur_module'=>'Editor' ) ).'">Edit this site</a></p>'."\n";
				else
					echo '<p>Please contact '.REASON_CONTACT_INFO_FOR_CHANGING_USER_PERMISSIONS.' when you are ready to make this site live.</p>'."\n";
				echo '</div>'."\n";
			}
			if($e->get_value( 'description' ))
			{
				echo '<div id="siteDesc">'."\n";
				if(strip_tags( $e->get_value( 'description' )) == $e->get_value( 'description' ))
				{
					echo nl2br( $e->get_value( 'description' ) );
				}
				else
				{
					echo $e->get_value( 'description' );
				}
				echo '</div>'."\n";
			}
			$sites = $this->admin_page->get_sites();
			if( count( $sites ) == 1 )
				parent::run();
			echo '</div>'."\n";
			echo '<div id="guide">'."\n";
			$es = new entity_selector();
			$es->add_type(id_of('type'));
			$es->add_right_relationship($e->id(),relationship_id_of('site_to_type'));
			$es->limit_tables();
			$es->limit_fields();
			$non_editable_es = carl_clone($es);
			$non_editable_es->add_right_relationship($e->id(),relationship_id_of('site_cannot_edit_type'));
			$noneditable_types = $non_editable_es->run_one();
			if(!empty($noneditable_types))
			{
				$es->add_relation('entity.id NOT IN ('.implode(',',array_keys($noneditable_types)).')');
			}
			$es->set_order('entity.name ASC');
			$types = $es->run_one();
			
			if(array_key_exists(id_of('minisite_page'),$types))
			{
				$page_type_array[id_of('minisite_page')] = $types[id_of('minisite_page')];
				unset($types[id_of('minisite_page')]);
				$types = array_merge($page_type_array, $types);
			}
			echo '<ul>'."\n";
			foreach($types as $type)
			{
					$es = new entity_selector($e->id());
					$es->set_sharing('owns');
					$es->add_type($type->id());
					$es->limit_tables();
					$es->limit_fields();
					$es->set_order('entity.last_modified DESC');
					if(reason_user_has_privs( $this->admin_page->user_id, 'edit' ))
					{
						$state = 'Live';
						$state_link_val = 'live';
					}
					else
					{
						$state = 'Pending';
						$state_link_val = 'pending';
					}
					$ents = $es->run_one($type->id(),$state);
					$ents_count = count($ents);
					$name = $type->get_value('plural_name') ? $type->get_value( 'plural_name' ) : $type->get_value( 'name' );
					
					echo '<li class="'.$type->get_value('unique_name').'" style="list-style-image:url('.reason_get_type_icon_url($type).')">';
					echo '<h4><a href="'.$this->admin_page->make_link( array( 'type_id' => $type->id(),'cur_module'=>'Lister','state'=>$state_link_val ) ).'">'.$name.'</a> <span class="count">('.$ents_count.')</span></h4>'."\n";
					if(!empty($ents))
					{
						echo '<div class="recent">'."\n";
						echo 'Recently edited:'."\n";
						echo '<ul>'."\n";
						$i = 1;
						foreach($ents as $ent_id=>$ent)
						{
							if ($i > 3) break;
							$name = strip_tags($ent->get_display_name());
							if(empty($name))
								$name = '[unnamed]';
							echo '<li class="item'.$i.'"><a href="'.$this->admin_page->make_link( array( 'type_id' => $type->id(),'id'=>$ent_id,'cur_module'=>'Editor', ) ).'">'.$name.'</a></li>'."\n";
							$i++;
						}
						echo '</ul>'."\n";
						echo '</div>'."\n";
						
					}
					echo '</li>'."\n";
			}
			echo '</ul>'."\n";
			echo '</div>'."\n";
			echo '</div>'."\n";
		} // }}}
	} // }}}
?>
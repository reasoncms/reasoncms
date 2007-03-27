<?php
	reason_include_once('classes/admin/modules/default.php');
	reason_include_once( 'function_libraries/images.php' );
	
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
			if( $e->get_value( 'description' ) )
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
			else
			{
				$sites = $this->admin_page->get_sites();
				if( count( $sites ) == 1 )
					parent::run();
			}
			echo '<div id="guide">'."\n";
			$es = new entity_selector();
			$es->add_type(id_of('type'));
			$es->add_right_relationship($e->id(),relationship_id_of('site_to_type'));
			$es->set_order('entity.name ASC');
			$types = $es->run_one();
			$es->add_right_relationship($e->id(),relationship_id_of('site_cannot_edit_type'));
			$noneditable_types = $es->run_one();
			if(array_key_exists(id_of('minisite_page'),$types))
			{
				$page_type_array[id_of('minisite_page')] = $types[id_of('minisite_page')];
				unset($types[id_of('minisite_page')]);
				$types = array_merge($page_type_array, $types);
			}
			echo '<ul>'."\n";
			foreach($types as $type)
			{
				if(!array_key_exists($type->id(),$noneditable_types))
				{
					$es = new entity_selector($e->id());
					$es->set_sharing('owns');
					$es->set_num(3);
					$es->add_type($type->id());
					$es->set_order('entity.last_modified DESC');
					$ents = $es->run_one();
					$ents_count = $es->get_one_count();
					
					$name = $type->get_value('plural_name') ? $type->get_value( 'plural_name' ) : $type->get_value( 'name' );
					
					echo '<li class="'.$type->get_value('unique_name').'">';
					echo '<h4><a href="'.$this->admin_page->make_link( array( 'type_id' => $type->id(),'cur_module'=>'Lister' ) ).'">'.$name.'</a> <span class="count">('.$ents_count.')</span></h4>'."\n";
					if(!empty($ents))
					{
						echo '<div class="recent">'."\n";
						echo 'Recently edited:'."\n";
						echo '<ul>'."\n";
						$i = 1;
						foreach($ents as $ent_id=>$ent)
						{
							echo '<li class="item'.$i.'"><a href="'.$this->admin_page->make_link( array( 'type_id' => $type->id(),'id'=>$ent_id,'cur_module'=>'Editor' ) ).'">'.strip_tags($ent->get_display_name()).'</a></li>'."\n";
							$i++;
						}
						echo '</ul>'."\n";
						/* if($ents_count > 3)
						{
							echo '<a href="'.$this->admin_page->make_link( array( 'type_id' => $type->id(),'cur_module'=>'Lister' ) ).'" class="more">more…</a>';
						} */
						echo '</div>'."\n";
						
					}
					echo '</li>'."\n";
				}
			}
			echo '</ul>'."\n";
			echo '</div>'."\n";
			echo '</div>'."\n";
		} // }}}
	} // }}}
?>
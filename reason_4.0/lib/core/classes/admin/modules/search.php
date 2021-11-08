<?php
/**
 * @package reason
 * @subpackage admin
 */

include_once('reason_header.php');
include_once( DISCO_INC . 'disco.php' );
reason_include_once( 'classes/entity_selector.php');
reason_include_once( 'function_libraries/user_functions.php' );
 /**
  * Include the default module
  */
	reason_include_once('classes/admin/modules/default.php');
	
	/**
	 * Exports reason entities by type in XML and CSV formats
	 * @todo export site entities as XML both individually and in the 'all-types export'
	 */
	class ReasonAdminSearchModule extends DefaultModule// {{{
	{
		protected $types;
		protected $sites;
		protected $form;
		function ReasonAdminSearchModule( &$page ) // {{{
		{
			$this->admin_page =& $page;
		} // }}}
		
		function init() // {{{
		{
			$this->admin_page->title = 'Search';
			$this->head_items->add_stylesheet(REASON_HTTP_BASE_PATH . 'css/reason_admin/search.css');
		} // }}}
		
		function run() // {{{
		{
			
			$sites = $this->get_sites();
			if(empty($sites))
			{
				echo '<p class="searchSorryMsg">Sorry, you don\'t have access to any sites to search.</p>';
				return;
			}
			
			echo '<div class="searchModule">';
			$form = $this->get_form();
			$form->run();
			
			if($form->successfully_submitted())
			{
				echo '<div class="searchResults">';
				echo '<h4>Search results</h4>';
				echo $this->get_results_html($form);
				echo '</div>';
			}
			
			if($this->user_is_admin())
			{
				echo '<p>See also: <a href="'.REASON_HTTP_BASE_PATH.'scripts/search/find_and_replace.php">Find and replace</a></p>'."\n";
			}
			
			echo '</div>';
		}
		
		protected function user_is_admin()
		{
			return reason_user_has_privs( $this->admin_page->user_id, 'view_sensitive_data' );
		}
		
		protected function get_types()
		{
			if(!isset($this->types))
			{
				$es = new entity_selector();
				$es->add_type(id_of('type'));
				if(!empty($this->admin_page->site_id))
				{
					$es->add_right_relationship($this->admin_page->site_id, relationship_id_of('site_to_type'));
				}
				$es->set_order('entity.name ASC');
				$this->types = $es->run_one();
			}
			return $this->types;
		}
		protected function get_sites()
		{
			if(!isset($this->sites))
			{
				$es = new entity_selector();
				$es->add_type(id_of('site'));
				$es->set_order('entity.name ASC');
				if(!$this->user_is_admin())
				{
					$es->add_left_relationship($this->admin_page->user_id, relationship_id_of('site_to_user'));
				}
				$this->sites = $es->run_one();
			}
			return $this->sites;
		}
		protected function get_form()
		{
			if(!isset($this->form))
			{
				$type_names = array(0 => 'All Types');
				foreach($this->get_types() as $id=>$arrtype)
				{
					$type_names[$id] = $arrtype->get_value('name');
				}

				$site_names = array(
					0 => $this->user_is_admin() ? 'All Sites' : 'All Your Sites',
					-1 => $this->user_is_admin() ? 'All Live Sites' : 'All Your Live Sites',
				);
				foreach($this->get_sites() as $id=>$arrsite)
				{
					$site_names[$id] = $arrsite->get_value('name');
				}
				
				$limit_options = array(
					10 => 10,
					100 => 100,
					250 => 250,
					500 => 500,
					1000 => 1000,
					10000 => 10000,
				);

				$this->form = new Disco;
				
				$this->form->set_box_class('StackedBox');
				$this->form->set_form_method('get');
				
				$this->form->add_element('search_string');
				$this->form->set_display_name('search_string', 'Search For');
				
				$this->form->add_element('type','select_no_sort',array('options'=>$type_names, 'reject_unrecognized_values' => true ));
				$this->form->set_display_name('type', 'Among');
				
				$this->form->add_element('search_site_id','select_no_sort',array('options'=>$site_names, 'reject_unrecognized_values' => true ));
				$this->form->set_display_name('search_site_id', 'Within');
				$this->form->set_value('search_site_id',$this->admin_page->site_id);
				
				$this->form->add_element('cur_module','hidden');
				$this->form->set_value('cur_module','Search');
				
				$this->form->add_element('user_id','hidden');
				$this->form->set_value('user_id',$this->admin_page->user_id);
				
				$this->form->add_element('site_id','hidden');
				$this->form->set_value('site_id',$this->admin_page->site_id);
				
				$this->form->add_element('result_limit','select_no_sort',array('options'=>$limit_options));
				$this->form->set_value('result_limit','100');
				$this->form->set_display_name('result_limit', 'Maximum Number of Results to Show');
				
				$this->form->actions = array('Search');
			}
			return $this->form;
		}
		
		protected function get_results_html($form)
		{
			$txt = '';
			$types = $this->get_types();
			$sites = $this->get_sites();
			$result_limit = (integer) $form->get_value('result_limit');
			if($form->get_value('search_string') && strlen($form->get_value('search_string')) > 1)
			{
				$sql_search_string = reason_sql_string_escape($form->get_value('search_string'));
				$use_fields = array('id','name','last_modified');
			
				$hit_count = 0;
				
				if($form->get_value('type'))
				{
					if(isset($types[$form->get_value('type')]))
					{
						$only_type = $types[$form->get_value('type')];
						$types = array($form->get_value('type') => $only_type);
					}
					else
					{
						$types = array();
					}
				}
				$site_ids = array();
				if( isset($sites[$form->get_value('search_site_id')]) )
				{
					$site_ids[] = (integer) $form->get_value('search_site_id');
				}
				elseif(-1 == $form->get_value('search_site_id'))
				{
					foreach($sites as $site)
					{
						if($site->get_value('site_state') == 'Live')
						{
							$site_ids[] = $site->id();
						}
					}
					if(empty($site_ids)) {
						$site_ids = array(-1);
					}
				}
				elseif( 0 == $form->get_value('search_site_id') )
				{
					if($this->user_is_admin())
					{
						$site_ids = null; // should be faster to simply not specify site ids
					}
					else
					{
						$site_ids = array_keys($sites);
						if(empty($site_ids)) {
							$site_ids = array(-1);
						}
					}
				}
				else
				{
					echo '<p>Invalid site</p>';
					return;
				}
				foreach($types as $type)
				{
					if($hit_count > $result_limit)
						break;
					
					//echo $type->get_value('name').'<br />';
					$tables = get_entity_tables_by_type( $type->id() );
					$es = new entity_selector($site_ids);
					$es->set_num($result_limit - $hit_count);
					$es->add_type($type->id());
					$tables = get_entity_tables_by_type( $type->id() );
					//pray($tables);
					$relation_pieces = array();
					foreach($tables as $table)
					{
						$fields = get_fields_by_content_table( $table );
						//pray($fields);
						foreach($fields as $field)
						{
							$relation_pieces[] = $table.'.'.$field.' LIKE "%'.$sql_search_string.'%"';
						}
					}
					$relation = '( '.implode(' OR ',$relation_pieces).' )';
					//echo '<p>'.$relation.'</p>';
					$es->add_relation($relation);
					//$es->add_relation('* LIKE "%'.$_REQUEST['search_string'].'%"');
					$entities = $es->run_one();
					if(!empty($entities))
					{
						$typename = $type->get_value('plural_name') ? $type->get_value('plural_name') : $type->get_value('name');
						$txt .= '<h5>'.$typename.'</h5>'."\n";
						$txt .= '<table cellpadding="5" cellspacing="0">'."\n";
						$txt .= '<tr>';
						foreach($use_fields as $field)
						{
							$txt .= '<th>'.prettify_string($field).'</th>';
						}
						$txt .= '<th>Owned By</th>';
						$txt .= '<th>Search Hits</th>';
						$txt .= '<th>Edit</th>';
						$txt .= '</tr>';
						$class = 'odd';
						foreach($entities as $e)
						{
							$txt .= '<tr class="'.$class.'">';
							foreach($use_fields as $field)
							{
								if($field == 'last_modified')
								{
									$txt .= '<td>'.date('j M Y',get_unix_timestamp($e->get_value($field))).'</td>'."\n";
								}
								elseif($field == 'name')
								{
									$txt .= '<td>'.$e->get_display_name().'</td>'."\n";
								}
								else
								{
									$txt .= '<td>'.$e->get_value($field).'</td>'."\n";
								}
							}
							$txt .= '<td>';
							// This is the one thing that could make for poor performance if there are a lot of results
							// I'm not savvy enough yet to know how to include the owner info in the original query
							$owner_site_id = get_owner_site_id( $e->id() );
							if(!empty($owner_site_id))
							{
								$owner_site = new entity(get_owner_site_id( $e->id() ) );
								$txt .= '<a href="'.$owner_site->get_value('base_url').'">';
								$txt .= $owner_site->get_value('name');
								$txt .= '</a>';
							}
							else
							{
								$txt .= 'Orphan Entity -- does not have an owner site';
							}
							$txt .= '</td>';
							$txt .= '<td>';
							$txt .= '<ul>';
							foreach($e->get_values() as $key=>$value)
							{
								if(stristr($value,$form->get_value('search_string')))
								{
									$search_str = $form->get_value('search_string');
									if($type->get_value('unique_name') == 'form' && 'thor_content' == $key)
			                                                {
									        $value = htmlspecialchars($value);
										$search_str = htmlspecialchars($search_str);						
									}
									if(function_exists('str_ireplace'))
										$value = str_ireplace($search_str,'<span class="hit">'.$search_str.'</span>',$value);
									else
										$value = preg_replace('/('.preg_quote($search_str).')/i','<span class="hit">\\0</span>',$value);
									$txt .= '<li><strong>'.$key.':</strong> '.$value.'</li>';
								}
							}
							$txt .= '</ul>';
							$txt .= '</td>';
							$txt .= '<td>';
							if(!empty($owner_site_id))
							{
								$txt .= '<a href="http://'.REASON_WEB_ADMIN_PATH.'?site_id='.$owner_site_id.'&amp;type_id='.$type->id().'&amp;id='.$e->id().'">Edit</a>';
							}
							$txt .= '</td>';
							$txt .= '</tr>'."\n";
							if( $class == 'odd' )
							{
								$class = 'even';
							}
							else
							{
								$class = 'odd';
							}
							$hit_count++;
						}
						$txt .= '</table>'."\n";
					}
				}
				if($hit_count < 1)
				{
					$txt .= '<p>No fields in Reason matched your search request.</p>'."\n";
				}
				else
				{
					$txt .= '<p><a href="#top">Top of Page</a></p>'."\n";
					echo '<p>Total matches: '.$hit_count.'</p>';
				}
			}
			elseif($form->get_value('search_string'))
			{
				echo '<p>Sorry, one-character searches aren\'t supported. Please enter more than one character for your search.</p>';
			}
			else
			{
				echo '<p>Please enter a search.</p>';
			}
			return $txt;
		}
	}
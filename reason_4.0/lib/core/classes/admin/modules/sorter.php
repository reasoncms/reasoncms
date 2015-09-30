<?php
/**
 * @package reason
 * @subpackage admin
 */
 
 /**
  * Include the default module
  */
	reason_include_once('classes/admin/modules/default.php');
	include_once('carl_util/basic/url_funcs.php');
	include_once(DISCO_INC . 'disco.php');
	
	
	/**
	 * An administrative module that provides a sorting widget for entities with the sort_order column
	 *
	 * @todo get things working for those with js off
	 * @todo figure out a drag-and-drop interface
	 */
	class sorter
	{
		var $admin_page;
		function sorter( &$page ) 
		{
			$this->admin_page = &$page;
		} 
		function init()
		{
			$es = $this->get_entity_selector();
			$this->values = $es->run_one();

			if( $this->is_new() )
			{
				$this->get_links();
				if( count( $this->links ) == 1 )
				{
					$l = unhtmlentities( current( $this->links ) );
					header( 'Location: ' . $l );
					die();
				}
			}
		} 
		function update_es( $es ) 
		{
			return $es;
		}
		/**
		 * Get the name of the database field to use for sorting
		 * @return string
		 */
		function get_field()
		{
			return 'sort_order';
		}
		/**
		 * Get the name of the database table to use for sorting
		 * @return string
		 */
		function get_table()
		{
			return 'sortable';
		}
		function show_extras() 
		{
			echo '&nbsp;';
		} 
		function get_entity_selector() 
		{
			$es = new entity_selector( $this->admin_page->site_id );
			$es->add_type( $this->admin_page->type_id );
			$es->set_order( $this->get_table().'.'.$this->get_field().' ASC' );
			$es->set_sharing( 'owns' );
			$es = $this->update_es( $es );
			return $es;
		} 
		function get_links() 
		{
			$link = $this->admin_page->make_link( array( 'default_sort' => false ) , true );
			$this->links = array( 'Sort All Items' => $link );
			return $this->links;
		} 
		function is_new() 
		{
			if( empty( $this->admin_page->request[ 'default_sort' ] ) )
				return false;
			else
				return true;
		} 
		function show_links() 
		{
			foreach( $this->links AS $name => $link )
				echo '<a href="'.$link.'">'.$name."</a><br />\n";
		} 
	}

	class SortingModule extends defaultModule
	{

		var $type_entity;
		var $sorter;

		function init() 
		{
			if(!reason_user_has_privs($this->admin_page->user_id, 'edit'))
			{
				return;
			}
			$this->admin_page->head_items->add_javascript(WEB_JAVASCRIPT_PATH .'jquery.mobile-tablednd.js');
			$this->admin_page->head_items->add_javascript(WEB_JAVASCRIPT_PATH .'sorter.js');
			$this->admin_page->head_items->add_stylesheet(REASON_HTTP_BASE_PATH . 'css/reason_admin/sorter.css');
			$type_entity = new entity( $this->admin_page->type_id );
			$this->type_entity = $type_entity;
			$this->admin_page->title = 'Sorting ' . prettify_string( $type_entity->get_value( 'plural_name' ) );
			
			if( $this->type_entity->get_value( 'custom_sorter' ) )
			{
				reason_include_once( 'content_sorters/' . $this->type_entity->get_value( 'custom_sorter' ) );
				$sorter = $GLOBALS[ '_content_sorter_class_names' ][ $this->type_entity->get_value( 'custom_sorter' ) ];
				$this->sorter = new $sorter( $this->admin_page );
			}
			else
				$this->sorter = new sorter( $this->admin_page );
			$this->sorter->init();
		} 
		function run() 
		{
			if(!reason_user_has_privs($this->admin_page->user_id, 'edit'))
			{
				echo 'Sorry. You do not have privileges to edit live items on this site.';
				return;
			}
			$fields = get_fields_by_type( $this->admin_page->type_id );			
			
			if( is_array($fields) && in_array( $this->sorter->get_field() , $fields ) )
			{
				if( $this->sorter->is_new() )
				{
					$this->sorter->show_links();
				}
				else
				{
					// Disco stuff goes here!
					$sorterForm = new SorterForm;
					$sorterForm->set_sorter($this->sorter);
					$sorterForm->user_id = $this->admin_page->user_id;
					$sorterForm->type_id = $this->admin_page->type_id;
					$sorterForm->site_id = $this->admin_page->site_id;
					$sorterForm->run();
//				var_dump(unhtmlentities( $_SESSION[ 'listers' ][ $this->admin_page->site_id ][ $this->admin_page->type_id ] ));
					if (isset($_GET['savedTime']))
					{
						$savedString = "This sorting last saved at: ".date("H:i:s m-d-Y", $_GET['savedTime']);
						
					}
					else
					{
						$savedString = "Not Saved.";
					}
					echo $savedString;
				}
			}
			else
				echo 'This type is not sortable.';
		} 
		/**
		 * @deprecated
		 * @todo remove method
		 */
		function set_order() 
		{
			trigger_error('set_order method on SortingModule no longer works.', HIGH);
			return;
		}
	}
	
	class SorterForm extends Disco
	{
		var $elements = array();
		var $required = array();
		var $sorted_values = array();
		var $sorter;
		var $actions =  array('Save', 'Go Back');
		var $user_id;
		var $type_id;
		var $site_id;
		
		function set_sorted_vals($vals)
		{
			$this->sorted_values = $vals;
		}
		/**
		 * Set the sorter object (of class "sorter") so the form can
		 * grab the values to sort and know what field to sort on
		 * @param object $sorter
		 * @return void
		 */
		function set_sorter($sorter)
		{
			$this->sorter = $sorter;
			$this->set_sorted_vals($this->sorter->values);
		}
		
		function on_every_time()
		{
			$num = count($this->sorted_values);
			$counter = 1;
			foreach( $this->sorted_values AS $v )
			{
				$options = array();
				for ($i = 1; $i<=$num; $i++)
				{
					if ($i != $counter)
					{
						if ($i != $counter + 1)
						{
							$tinyI = $i - .5;
							$options["$tinyI"] = "Move before $i";
						}
					}
					else 
					{
						$tinyI = $i - .5;
						$default = $tinyI;
						$options["$tinyI"] = "Don't move";
						
					}
				}
				$anotherTinyI = $i - .5;
				$options["$anotherTinyI"] = "Move after " . ($i - 1);
				$args['display_name'] = "$counter. " . strip_tags( $v->get_display_name() );
				$args['options'] = $options;
				$counter++;
				$myid = $v->get_value('id');
				$element_name = "sortOrder_{$myid}";
				$this->add_element($element_name, 'select_no_sort', $args);
				$this->set_value($element_name, $default);
			}
		}


		function process()
		{
			if ($this->get_value('chosen_action') == '0')
			{
				$items = array();
				foreach ($this->get_element_names() as $name)
				{
					if (preg_match("/^sortOrder_/i", $name))
					{
						$order = $this->get_value($name);
						$name = preg_replace("/^sortOrder_/i", "", $name);
						$items[$name] = $order;
					}
				}
				asort($items);
				$sort_order = 1;
				foreach ($items as $k => $v)
				{
					$items[$k] = $sort_order;
					$sort_order++;
				}
				
				$changed_something = false;
				if (!empty($items))
				{
					foreach( $items AS $id => $order )
					{
						$result = reason_update_entity($id, $this->user_id, array($this->sorter->get_field() => $order), false);
						if ($result) $changed_something = true;
					}
				}
				
				// if we have changed sort order of pages, lets try to drop the nav cache for the active site.
				if ($changed_something && ($this->type_id == id_of('minisite_page')))
				{
					reason_include_once('classes/object_cache.php');
					$cache = new ReasonObjectCache($this->site_id . '_navigation_cache');
					$cache->clear();
				}
			}

		}
		
		function where_to() 
		{
			if ($this->get_chosen_action() == '0')
				{
				return @carl_make_redirect(array("savedTime" => time()));
				}
			else 
				{
				return carl_make_redirect(array('cur_module'=>'Lister', 'state'=>'live'));
				}
		}
	}
?>
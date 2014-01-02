<?php
/**
 * @package reason
 * @subpackage content_deleters
 */
 	/**
 	 * Register deleter with Reason and include dependencies
 	 */
	$GLOBALS[ '_reason_content_deleters' ][ basename( __FILE__) ] = 'site_deleter';
	
	reason_include_once( 'classes/admin/admin_disco.php' );
	reason_include_once( 'classes/entity.php' );
	reason_include_once( 'classes/url_manager.php' );

	/**
	 * A content deleter that handles deletion of sites
	 * @author Ben White
	 **/
	class site_deleter extends deleteDisco
	{
		/**
		* Check if this site should display the delete form.
		*
		* Sets show_form to false if site is master admin, and runs get_borrowing_list which checks if the site is borrowed by another.
		**/
		function pre_show_form() 
		{
			$site = new entity( $this->get_value( 'id' ) );	
			if($site->get_value('unique_name') == 'master_admin')
			{
				echo "The Master Admin site is not deletable.";
				$this->show_form = false;
			}	
			else
			{
				if($site->get_value('state') == 'Live' || $site->get_value('state') == 'Deleted')
				{
					$this->get_borrowing_list();	
				}
			}
		}
		/**
		* Returns a string containing markup for a list of entities owned by this site that are borrowed.
		*
		* Format is <h4>Type</h4> <ul> <li> Entity <a href="Link to move entity ownership">Move</a></li></ul>
		* Should return a blank if site is master admin.
		*
		* Called when show_form is false, indicating that the delete form is blocked by borrowing.
		**/
		function no_show_form()
		{
			$output_string = '';
			$site = new entity( $this->get_value( 'id' ) );	
			$borrowing_array = $this->get_borrowing_list();
			$output_string .= $site->get_value('name')." cannot be deleted at the moment, because some of its entities are borrowed by other sites. Before deleting this site you must first move all borrowed entities to other sites.";
			$output_string .= '<h3>Entities borrowed by other sites</h3>';
			foreach($borrowing_array as $type_id => $entity_ids)
			{
				if(empty($entity_ids))
					continue;
				$type_entity = new entity($type_id);
				$items = array();
				foreach($entity_ids as $entity_id => $borrowing_sites)
				{
					if(empty($borrowing_sites))
						continue;
					$borrowing_site_names = array();
					foreach($borrowing_sites as $borrowing_site)
					{
						$borrowing_site_names[] = $borrowing_site->get_value('name');
					}
					$entity_object = new entity($entity_id);
					$entity_name = $entity_object->get_display_name();
					$spacing = "<br />";
					if (strpos($entity_name,"<br>")!== false)
					{
						$spacing = "";
					}
					$items[] = '<li><strong>'.$entity_name.'</strong> (id: '. $entity_object->id().')'.$spacing.'borrowed by '.implode(', ',$borrowing_site_names).'</li>';
				}
				if(!empty($items))
				{
					$type_name = $type_entity->get_value('plural_name') ? $type_entity->get_value('plural_name') : $type_entity->get_value('name');
					$output_string .= "<h4>".$type_name."</h4>";
					$output_string .= "<ul>";
					$output_string .= implode('',$items);
					$output_string .= "</ul>";
					$output_string .= '<p><a href="'.REASON_HTTP_BASE_PATH.'scripts/move/move_entities_among_sites_2.php?site_id='.$site->id().'&type_id='.$type_entity->id().'">Move these '.$type_name.'</a> (opens in new window)</p>';
				}
							
			}		
			
			if($site->get_value('unique_name')=='master_admin')
			{
				$output_string = "";
			}
			return $output_string;
		}
		/**
		* Automatically called each time the form loads.
		* 
		* Checks the show_form variable to determine if the deletion page should be shown.
		* Displays each entity that will be deleted with the site, in the format
		* <h4>Type</h4> <ul> <li> Entity <a href = "Preview of entity"> Preview </a> </li> </ul>
		*
		* Contains javascript to display a confirmation window after delete is submitted.
		**/	
		function show_form()
		{	
			if($this->show_form)
			{
				$site = new entity( $this->get_value( 'id' ) );
				$action_word = 'Deleting';
				if('Deleted' == $site->get_value('state'))
					$action_word ='Expunging';
				echo $action_word." a site will delete all entities it owns. For ".$site->get_value('name')." this includes:<br>";
				$borrowing_array = $this->get_borrowing_list();
				foreach($borrowing_array as $type_id => $entities)
				{
					$type_entity = new entity($type_id);
					$type_name = $type_entity->get_value('plural_name') ? $type_entity->get_value('plural_name') : $type_entity->get_value('name');
					$temp_string = "\n<h4>".$type_name."</h4>\n<ul>";
					foreach(array_keys($entities) as $entity_id)
					{
						$link_address = '<a href="?site_id='.$site->id().'&amp;type_id='.$type_id.'&amp;id='.$entity_id.'&amp;cur_module=Preview" target="_blank">Preview</a> (id: '.$entity_id.')';
						$entity_object = new entity($entity_id);
						$entity_name = $entity_object->get_display_name();
						$temp_string .= "\n<li>".$entity_name." ".$link_address."</li>";
					}
					$temp_string .= "\n</ul>";
					echo $temp_string;
				}	
				parent::show_form();
				echo "<script>";
				echo "\n$(document).ready(function(){";
				echo "\n$('form#disco_form').submit(function(event){";
				echo "\nif(window.confirm('".$action_word." this site will delete all entities listed. Are you sure you want to proceed?')){";
				echo "\n}";
				echo "\nelse{";
				echo "\nevent.preventDefault();";
				echo "\ncancelDisableSubmit();";
				echo "\n}";
				echo "\n});";
				echo "\n});";
				/*
				echo "\ndocument.getElementById('disco_form')[0].onsubmit=function() {return displayWarning(this)}";
				echo "\nfunction displayWarning(item)\n{\n\na = window.confirm('Deleting this site will delete all entities listed. Are you sure you want to proceed?')\nif(a!=true)\n{\nreturn false\n}\n}";
				*/
				echo "\n</script>";
			}
			else
			{
				echo $this->no_show_form();
			}
		}
		/**
		* This function does the actual deleting once the button is pressed.
		*
		* The main function is to check whether or not a page is master admin,
		* and then deleting all entities that are owned by  page if it is not.
		* Rewrites are updated after the pages are deleted.
		*/ 
		function delete_entity()
		{
			$user_id = $this->admin_page->authenticate();
			$url_manager = new url_manager($this->get_value('id'));
			$site = new entity( $this->get_value('id'));
			if(!($site->get_value('unique_name')=='master_admin'))
			{
				$owned_entities = $this->get_owned_entities($site);
				foreach($owned_entities as $type_id => $array)
				{
					foreach($array as $entity)
					{
						reason_update_entity( $entity->id(), $user_id, array('state'=>'Deleted'), false);
					}
				}
				parent::delete_entity();
				$url_manager->update_rewrites();
			}
		}
		/**
		* This function returns an array with the structure type_id=>entities
		*
		* in which the entities contained are owned by the site entity that is passed as an arguent.
		* Entity selectors to find a list of types as well as the owned entities are used.
		*/
		function get_owned_entities($site)
		{
			$types = new entity_selector();
			$types->add_type(id_of('type'));
			$typelist = $types->run_one();
			$site_entity_selector = new entity_selector($site->id());
			$owned_entities = array();
			foreach($typelist as $type_id => $type)
			{
				$site_entity_selector->add_type($type_id);
				$site_entity_selector->set_sharing('owns');
				$temp_owned_entities = $site_entity_selector->run_one($type_id);
				if(!empty($temp_owned_entities ))
				{
					$owned_entities[$type_id] = $temp_owned_entities;
				}	
			}
			return $owned_entities;
		}
		/**
		* Returns the relationship entity of the borrowing relationship for the type of the argument $item.
		*
		* If $item is an image, this would be site_borrows_image, for example.
		*/
		function get_borrowing_sites($item)
		{	
			$borrowing_relations = null;
			if($item->has_right_relation_of_type(get_borrows_relationship_id($item->get_value('type'))))
			{
				$borrowing_relations = $item->get_right_relationship(get_borrows_relationship_id($item->get_value('type')));
				
			}
			return $borrowing_relations;
		}
		/**
		* Generates an array that has the structure type_id=>entity_id=>borrowing_relation
		* for this site, then returns that array. 
		*
		* For example, one structure could contain
		* id_of('image')=>id_of('this_image')=>site 1, site 2, if this site owned an entity
		* this_image of type image borrowed by site 1 and site 2.
		*/
		function get_borrowing_list()
		{
			if (!isset($this->borrowing_array))
			{
				$site = new entity( $this->get_value('id') );	
				$this->borrowing_array = array();
				$site_owned_entities = $this->get_owned_entities($site);
				foreach($site_owned_entities as $type_id => $entities)
				{
					$this->borrowing_array[$type_id] = array();
					foreach($entities as $entity)
					{			
						$borrowing_sites = $this->get_borrowing_sites($entity);
						if(!empty($borrowing_sites))
						{
							$this->show_form = false;
						}
						$this->borrowing_array[$type_id][$entity->id()]=$borrowing_sites;	
					}
				}
			}
			return $this->borrowing_array;
		}
		function post_show_form()
		{
			$text = $this->show_form ? 'Cancel' : 'Back';
			echo '<p class="cancel"><a href="'.$this->admin_page->make_link( array( 'cur_module' => 'Preview' ) ).'">'.$text.'</a></p>';
		}
	}
?>

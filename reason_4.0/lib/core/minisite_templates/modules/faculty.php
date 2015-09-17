<?php
/**
 * @package reason
 * @subpackage minisite_modules
 */
 
	/**
	 * Register module with Reason and include dependencies
	 */
	$GLOBALS[ '_module_class_names' ][ basename( __FILE__, '.php' ) ] = 'FacultyStaffModule';

	reason_include_once( 'minisite_templates/modules/default.php' );
	include_once( CARL_UTIL_INC . 'dir_service/directory.php' );
	
	/**
	 * usort function for sorting a set of directory results by last name
	 *
	 * Note: uses strcomp(), so it may not return a good alpha sort for non-ascii chars
	 *
	 * @param array $a an array with a ds_lastname key holding an array value with a string at position 0
	 * @param array $b an array with a ds_lastname key holding an array value with a string at position 0
	 */
	function dir_result_last_name_sort( $a, $b )
	{
		return strcmp( $a['ds_lastname'][0], $b['ds_lastname'][0] );
	}
	
	/**
	 * A minisite module that lists a set of people
	 *
	 * By default, lists individuals who have faculty/staff entities in the current site
	 *
	 * Can be extended to list people automatically via the directory service; overload the
	 * build_dept_filter() method to map a site field to an LDAP-style filter.
	 *
	 * @todo This class needs intensive documentation.
	 */
	class FacultyStaffModule extends DefaultMinisiteModule
	{
		var $directory_people = array();
		var $directory_netids = array();
		var $reason_people = array();
		var $reason_netids = array();
		var $reason_people_dir_info = array();
		var $all_people = array();
		var $affiliations = array(); // defines optional affiliation display order and naming
		var $affiliation_nav_names = array(); // defines optional affiliation names for use in navigation
		var $affiliation_from_directory = array();
		var $sorted_people = array();
		var $heads = true;
		var $other_affiliation_flag = false;
		var $affiliations_to_use_other_aff_flag = array();
		var $required_attributes = array('ds_email','ds_fullname','ds_lastname','ds_affiliation','ds_phone');
		var $acceptable_params = array(
			'thumbnail_width' => 0,
			'thumbnail_height' => 0,
			// How to crop the image to fit the size requirements; 'fill' or 'fit'
			'thumbnail_crop' => '',
			'ignore_affiliations' => false,
			// optional affiliation display order and naming. Set a label to null or '' to hide an affiliation.
			'affiliations' => array(),
			// optional affiliation names for use in navigation
			'affiliation_nav_names' => array(),
		);
				
		function has_content() // {{{
		{
			return true;
		} // }}}

		function init( $args = array() )
		{
			parent::init($args);
			if (!empty($this->params['affiliations']))
				$this->affiliations = $this->params['affiliations'];
			if (!empty($this->params['affiliation_nav_names']))
				$this->affiliation_nav_names = $this->params['affiliation_nav_names'];
		}
		
		function run() // {{{
		{
			$this->show_faculty_page();
		} // }}}
		function show_faculty_page() // {{{
		{
			echo '<div id="facultyStaff">'."\n";
			
			$this->get_directory_people();
			$this->process_directory_people();
			
			$this->get_reason_people();
			$this->process_reason_people();

			$this->look_up_dir_reason_diffs();
			
			$this->merge_people_arrays();
			$this->sort_all_people();
			
			$this->show_people();
			
			echo '</div>'."\n";
			
		} // }}}
		function get_directory_people() // {{{
		{
			$filter = $this->build_dept_filter();
			if (empty($filter))
				$this->other_affiliation_flag = false;
			else
			{
				$dir = new directory_service();
				$dir->search_by_filter($filter, $this->required_attributes);
				$records = $dir->get_records();
				if(!empty($records))
				{
					$this->directory_people = $records;
					usort( $this->directory_people, 'dir_result_last_name_sort' );
				}	
			}
		} // }}}
		function build_dept_filter()
		{
			return '';
		}
		function process_directory_people() // {{{
		{
			foreach( $this->directory_people AS $directory_person )
			{
				$this->directory_netids[ $directory_person[ 'ds_username' ][0] ] = $directory_person;
			}
		} // }}}
		function get_reason_people() // {{{
		{
			// grab all reason entries of faculty/staff for this site
			$es = new entity_selector( $this->parent->site_id );
			$es->add_type( id_of('faculty_staff' ) );
			$es->set_order( 'sortable.sort_order' );
			$this->reason_people = $es->run_one();
		} // }}}
		function process_reason_people() // {{{
		{
			foreach( $this->reason_people as $reason_person )
				$this->reason_netids[ $reason_person->get_value('name') ] = $reason_person;

		} // }}}
		function look_up_dir_reason_diffs() // {{{
		{
			// these are the people in Reason but not in the directory
			$to_lookup = array_diff( array_keys( $this->reason_netids ), array_keys( $this->directory_netids ) );

			// get LDAP info for those people
			foreach( $to_lookup AS $username )
			{
				$dir = new directory_service();
				$filter = $this->build_person_filter($username);
				if ($dir->search_by_filter($filter, $this->required_attributes))
					$this->reason_people_dir_info[ $username ] = $dir->get_first_record();
			}
			
		} // }}}
		function build_person_filter($username)
		{
			return '(ds_username='.$username.')';
		}
		function merge_people_arrays() // {{{
		{
			// the order should be like this:
			// all Reason entries sorted by sort_order
			// all directory entries not in Reason sorted by last name
			// once we sort the full list, we should be fine since 
			// putting the people into the staff and faculty buckets
			// won't change the order

			// first, run through Reason entries
			foreach( $this->reason_netids AS $reason_netid => $jar )
			{
				// If this person is in Reason but not the directory query, get the
				// values from the second set of directory queries
				if( !empty( $this->reason_people_dir_info[ $reason_netid ] ) )
				{
					$this->all_people[] = $this->reason_people_dir_info[ $reason_netid ];
					$this->affiliation_from_directory[$reason_netid] = false;
				}
				// otherwise, just grab from the original results
				elseif ( !empty( $this->directory_netids[ $reason_netid ] ) )
				{
					$this->all_people[] = $this->directory_netids[ $reason_netid ];
					// we reuse directory_netids below, so we need to kill this entry
					// to avoid double entries
					unset( $this->directory_netids[ $reason_netid ] );
					$this->affiliation_from_directory[$reason_netid] = true;
				}
				else
					;	// drop this person - no Reason or LDAP info
				}
			// now, run through what's left
			foreach( $this->directory_netids AS $directory_person )
			{
				$this->all_people[] = $directory_person;
				$this->affiliation_from_directory[$directory_person['ds_username'][0]] = true;
			}
		} // }}}
		function sort_all_people() // {{{
		{
			if(!empty($this->affiliations) && !$this->params['ignore_affiliations'])
			{
				// make sure sorted people affiliations are in same order as affiliations array
				foreach( $this->affiliations as $directory_aff=>$public_aff )
				{
					$this->sorted_people[$directory_aff] = array();
				}
				foreach( $this->all_people AS $person )
				{
					$hidden = false;
					// if this person has an entry in reason and they have an affiliation set in reason
					if( !empty( $this->reason_netids[ $person[ 'ds_username' ][0] ] ) &&
						$this->reason_netids[ $person[ 'ds_username' ][0] ]->get_value('affiliation') )
					{	
						$directory_aff = $this->reason_netids[ $person[ 'ds_username' ][0] ]->get_value( 'affiliation' );
					}
					elseif(!($directory_aff = $this->get_affiliation($person)))
						$directory_aff = 'other';
					
					if ((empty($this->affiliation_from_directory[$person['ds_username'][0]]) ||
						$this->affiliation_from_directory[$person['ds_username'][0]] == false) && 
					    (in_array($directory_aff,$this->affiliations_to_use_other_aff_flag)) && 
					    ($this->other_affiliation_flag == true))
					{
						$directory_aff = 'other_' . $directory_aff;
					}
					if( !empty( $this->reason_netids[ $person[ 'ds_username' ][0] ] ) &&
						$this->reason_netids[ $person[ 'ds_username' ][0] ]->get_value('show_hide')=='hide' )
					{		
							$hidden = true;
					}
					if(array_key_exists($directory_aff, $this->affiliations) && !$hidden )
					{
						$this->sorted_people[$directory_aff][$person['ds_username'][0]] = $person;
					}
				}
			}
			else
			{
				foreach( $this->all_people AS $person )
				{
					$this->sorted_people['all'][$person['ds_username'][0]] = $person;
				}
			}
		} // }}}
		function show_people() // {{{
		{
			$this->clean_sorted_people();
			$this->determine_heads_use();
			if($this->heads) $this->show_section_links();
			foreach($this->sorted_people as $affiliation=>$people)
			{

				if(!empty($people))
				{
					if($this->heads)
					{
						if (isset($this->affiliations[$affiliation]))
						{
							if (!$display = $this->affiliations[$affiliation])
								continue;
						}
						else
							$display = ucwords($affiliation);

						echo '<h3 class="facStaffHead"><a name="'.preg_replace('/\s+/','_', $affiliation).'">'.$display.'</a></h3>'."\n";
					}
					$this->list_people( $people );
				}
			}
		} // }}}

		function show_section_links() // {{{
		{
			$affiliations = array_keys($this->sorted_people);
			if (count($affiliations) > 1)
			{
				echo '<div class="facStaffNavLinks">';
				foreach($affiliations as $aff)
				{
					if (isset($this->affiliation_nav_names[$aff]))
						$display = $this->affiliation_nav_names[$aff];
					else if (isset($this->affiliations[$aff]))
						$display = $this->affiliations[$aff];
					else
						$display = ucwords($aff);
						
					$links[] = '<a class="facStaffNavLink" href="#'.preg_replace('/\s+/','_', $aff).'">'.$display.'</a>';
				}
				echo join('<span class="divider"> | </span>', $links);
				echo '</div>'."\n";
			}
		} // }}}
		
		/**
		  * Hide an affiliation if it is empty, or its label in the $affiliations var is empty.
		  *
		  */
		function clean_sorted_people() // {{{
		{
			foreach($this->sorted_people as $affiliation=>$people)
			{
				if(empty($people) || (array_key_exists($affiliation, $this->affiliations) && empty($this->affiliations[$affiliation])))
					unset($this->sorted_people[$affiliation]);
			}
		} // }}}
		function determine_heads_use() // {{{
		{
			if(count($this->sorted_people) > 1)
				$this->heads = true;
			else
				$this->heads = false;
		} // }}}
		function list_people( $people ) // {{{ // {{{
		{
			foreach( $people AS $person )
			{
				echo '<div class="facStaff">'."\n";
				$person = $this->process_person( $person );
				$this->show_person( $person );
				echo '</div>'."\n";	
			}
		} // }}} // }}}
		function process_person( $person ) // {{{
		{
			$person = $this->process_name( $person );
			$person = $this->process_title( $person );
			$person = $this->process_phone( $person );
			$person = $this->process_email( $person );
			$person = $this->process_content( $person );
			return $person;
		} // }}}
		function process_name( $person ) // {{{
		{
			$person['full_name'] = $person['ds_fullname'][0];
			return $person;
		} // }}}
		function process_title( $person ) // {{{
		{
			if (!empty ( $person['title'] ) && is_array( $person['title'] ) )
				$person[ 'title' ] = implode( "<br />\n", $person['title'] );
			return $person;
		} // }}}
		function process_phone( $person ) // {{{
		{
			if ( !empty ( $person['ds_phone'] ))
			{
				if( is_array( $person[ 'ds_phone' ] ) )
				{
					$person[ 'ds_phone' ] = implode(', ',$person[ 'ds_phone' ] );
				}
			}
			return $person;
		} // }}}
		function process_email( $person ) // {{{
		{
			if (!empty( $person['ds_email'] ))
			{
				$person['mail'] = strtolower($person['ds_email'][0]);
			}
			return $person;
		} // }}}
		function process_content( $person ) // {{{
		{
			if (!empty( $this->reason_netids[ $person['ds_username' ][0] ] ) )
			{
				if( $this->reason_netids[ $person[ 'ds_username' ][0] ]->get_value( 'content' ) )
				{
					$person['content'] = $this->reason_netids[ $person[ 'ds_username' ][0] ]->get_value( 'content' );
				}
			}
			return $person;
		} // }}}
		/**
		* Allows extending code to determine affiliations in ways beyond returning the raw directory data
		* @return mixed affiliation or false
		*/
		function get_affiliation( $person ) // {{{
		{
			if (!empty( $person['ds_affiliation'] ))
			{
				return $person['ds_affiliation'][0];
			}
			return false;
		} // }}}
		function show_image( $person ) // {{{
		{
			$image_id = '';
			if( !empty( $this->reason_netids[ $person[ 'ds_username' ][0] ] ) )
				$image_id = $this->grab_faculty_image( $this->reason_netids[ $person[ 'ds_username' ][0] ] );
			if (!empty($image_id))
			{
				$sized = $this->get_sized_image($image_id);
				echo "\t<div class='facStaffImage'>";
				show_image( $sized, false,true,false );
				echo "</div>\n";
			}
		} // }}}
		function show_person( $person ) // {{{
		{
			$this->show_image($person);
			echo '<a name="'.$person['ds_username'][0].'"></a>'."\n";
			echo '<div class="facStaffName">'.$person['full_name'];
			echo '</div>'."\n";
			if( !empty( $person[ 'ds_phone' ] )
				|| !empty( $person[ 'mail' ] )
				|| !empty( $person['content' ] ) )
			{
				echo '<div class="facStaffInfo">'."\n";
				if ( !empty ( $person['ds_phone'] ))
					echo '<div class="facStaffPhone"><span class="label">Phone: </span><span class="phoneNumber">'.$person['ds_phone'].'</span></div>'."\n";
				if ( !empty ( $person['mail'] ))
				{
					echo '<div class="facStaffEmail"><span class="label">Email: </span><a href="mailto:'.$person['mail'].'" class="emailAddress">'.$person['mail'].'</a></div>'."\n";
				}
				if (!empty( $person['content' ] ) )
				{
					echo '<div class="facStaffContent">' . $person[ 'content' ]  . '</div>'."\n";
				}
				echo '</div>'."\n";
			}
		} // }}}
		function grab_faculty_image( $person ) // {{{
		{
			if (empty($this->parent->textonly))
			{
				$images = $person->get_left_relationship( 'faculty_staff_to_image' );
				if( $images )
					return $images[0]->id();
				else
					return false;
			}
			else
				return false;
		} // }}}
		/**
		 * @return mixed a reasonSizedImage object or the image id if no sizing required
		 */
		function get_sized_image($image_id)
		{
			if($this->params['thumbnail_width'] != 0 or $this->params['thumbnail_height'] != 0)
			{
				$rsi = new reasonSizedImage();
				$rsi->set_id($image_id);
				if($this->params['thumbnail_width'] != 0)
				{
					$rsi->set_width($this->params['thumbnail_width']);
				}
				if($this->params['thumbnail_height'] != 0)
				{
					$rsi->set_height($this->params['thumbnail_height']);
				}
				if($this->params['thumbnail_crop'] != '')
				{
					$rsi->set_crop_style($this->params['thumbnail_crop']);
				}
				return $rsi;
			}
			return $image_id;
		}
		/**
		*  Template calls this function to figure out the most recently last modified item on page
		* This function uses the most recently modified faculty/staff member
		* @return mixed last modified value or false
		*/
		function last_modified() // {{{
		{
			$es = new entity_selector( $this->parent->site_id );
			$es->add_type( id_of('faculty_staff' ) );
			$temp = $es->get_max( 'last_modified' );
			if(!empty($temp))
			{
				return $temp->get_value( 'last_modified' );
			}
			else
			{
				return false;
			}
		} // }}}
		
		function get_documentation()
		{
			return'<p>Displays a list of people associated with this site</p>';
		}
	}
?>


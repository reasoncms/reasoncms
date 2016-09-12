<?php
	include_once( 'plasmature/plasmature.php' );

	/**
	*  The interface for groups of plasmature objects; should be extended.
	*  Will include 
	*  @package disco
	*/
	
	class ElementGroup //{{{
	{
		var $type = 'default';
		var $unique_name;
		var $display_name;
		
		/**
		* Array of the plasmature elements that are members of this group.  Each element object is a reference (NOT a copy) to 
		* the corresponding element stored in disco, so be careful not to stomp on them.
		* @var array format $unique_name => plasmature object
		*/
		var $elements = array();
		
		/**
		* Additional info for each element in this group; e.g., whether or not an element is required
		* @var array format $unique_name => array of info for this object
		*/
		var $additional_element_info = array();	//unique_name => array()

		/**
		* Comments for the entire element group - will be displayed after the element group.
		* @var string
		*/		
		var $comments; 
		/**
		* Whether or not the element group display name should be shown when displaying the element group
		* @var boolean
		*/
		var $use_group_display_name = true;
		/**
		* Whether or not the display names of element group members should be shown when displaying the group
		* @var boolean
		*/
		var $use_element_labels = true;
		/**
		* True if this element group should span more than one column of the box class
		* @var boolean
		*/
		var $span_columns = false;
		/**
		* True if the entire element group is required
		* @var boolean
		*/
		var $_is_required = false;
		/**
		* Names of the elements in the order in which they should be displayed
		* @var array
		*/
		var $order;	

		/**
		*  Initializes a new element group
		*  @param array $args Arguments for the group; any class variables may be set here.
		*/		
		function init ( $args = array())
		{
			$class_variables = get_class_vars(get_class($this));
			foreach($args as $var_name => $var_value)
			{
				if(array_key_exists($var_name, $class_variables) && empty($this->var_name))
				{
					$this->$var_name = $var_value;
				}
			}
		}

		/**
		*  Prints the HTML to display the element group.
		*/
		function display()
		{
			echo $this->get_display();
		}
		
		/**
		*  Generates the HTML to display the element group.
		*  @return string The markup to display the group
		*/	
		function get_display()
		{
			trigger_error('This method must be overloaded', WARNING);
		}
		
		/**
		* Adds an element to the element group.  (note: only one element may be added at a time, since we need
		* refences to the elements in disco, not copies.)
		* @param mixed $plasmature_object Reference to a plasmature element in disco
		* @param array $args Additional information for the element 
		*/		
		function add_element (&$plasmature_object, $args = '')
		{
			$this->elements[$plasmature_object->name] =& $plasmature_object;
			if(!empty($args))		
				$this->additional_element_info[$plasmature_object->name] = $args;
		}

		function remove_element($element_name)
		{
			//remove from the $elements array
			unset($this->elements[$element_name]);
			//remove from $additional_element_info
			unset($this->additional_element_info[$element_name]);
		}

		/**
		*  Updates reference to a plasmature element in disco when the disco element has been replaced.
		*  @param mixed $plasmature_object Reference to a plasmature element in disco
		*/
		function update_element(&$element)
		{
			$this->elements[$element->name] =& $element;
		}
	
		/**
		* Returns the names of the member elements for this element group
		* @return array Names of the member elements in this element group
		*/
		function get_element_names()
		{
			return array_keys($this->elements);
		}


		/**
		* Returns true if all of the elements in this group are required
		* @return boolean 
		*/
		function is_required()
		{
			if(!$this->_is_required)
			{
				$all_are_required = true;
				foreach($this->elements as $element_name => $element)
				{
					if(!$this->additional_element_info[$element_name]['is_required'])
					{
						$all_are_required = false;
						break;
					}
				}
				$this->_is_required = $all_are_required;
			}
			return $this->_is_required;
		}

		/**
		* Sets the unique name of this element group.
		* @param string $name 
		*/
		function set_name($name)
		{
			$this->unique_name = $name;
		}
		
		/**
		* Sets the display name of this element group.
		* @param string $name 
		*/
		function set_display_name($name)
		{
			$this->display_name = $name;
		}
		
		/**
		* Returns the display name of this element group if $this->use_group_display_name is true.  If no display name
		* is set, returns the unique name of the element group.
		* @return string display name
		*/
		function get_display_name()
		{
			if($this->use_group_display_name)
			{
				if($this->display_name)
					$name = $this->display_name;
				else
					$name = $this->unique_name;
			}
			else
					$name = ' ';
			return $name;
		}
		
		/**
		* Returns true if this group has a display name
		* @return boolean
		*/
		function has_display_name()
		{
			$name = $this->get_display_name();
			$name = trim($name);
			if(!empty($name))
				return true;
			else
				return false;
		}
		
		function set_comments( $content )
		{
			$this->comments = $content;
		} 
		function add_comments( $content )
		{
			$this->comments .= $content;
		}
		function get_comments()
		{
			return $this->comments;
		}	
		function echo_comments()
		{
			echo $this->get_comments();
		} 
		
		function set_order ($element_order)
		{
			$elements = $this->elements;
			$complete_order = array();
			foreach($element_order as $element_name)
			{
				if($this->element_is_in_group($element_name))
				{
					$complete_order[] = $element_name;
					unset($elements[$element_name]);
				}
				else
					trigger_error ($element_name.' is not a member of this element group.');
			}
			if(!empty($elements))
				$complete_order = array_merge($complete_order, $elements);
			
			$this->order = $complete_order;
		}
		
	
		function element_is_in_group($element_name)
		{
			if(!empty($this->element[$element_name]))
				return true;
			else
				return false;
		}
	
		function get_element_name($element)
		{
			if($element->display_name)
				$name = $element->display_name;
			else
				$name = $element->name;
			$name = $this->get_label($name); 
			if($this->additional_element_info[$element->name]['is_required'])
				$name .= '*';
			return $name;
		}	
	
		//from the box class
		function get_label ($label)
		{
			$trimmed_label = trim($label);  //this is just so we know not to add punctuation if this is a blank label
			$stripped_label = preg_replace( '/&nbsp;/' , '' , $label ); //if someone wants to leave an intentionally blank label, let them by using a nbsp as a lable
			$stripped_label = trim( $stripped_label ); 
			
			$label_punct = (substr($stripped_label, -1) == ':' || substr($stripped_label, -1) == '?' || empty($trimmed_label)) ? '' : ':';
			if( !empty( $stripped_label) )
				return $label.$label_punct;
			else
				return '';
		}
	
	} // }}}
	
	/**
	*  @package disco
	*/
	class ElementInLine extends ElementGroup
	{
		var $type = 'inline';
		
		function get_display()
		{
			$markup_string = '';
			
			if(!empty($this->order))
				$order = $this->order;
			else
				$order = $this->elements;
			
			foreach ($order as $element_name => $element)
			{
				$markup_string .= '<span id="'.str_replace('_', '', $element_name).'" class="inlineElement">'.$this->get_individual_element_display($element_name).'</span>';
			}
			return $markup_string;
		}
		
		function get_individual_element_display($element_name)
		{
			$markup_string = '';
			$element = $this->elements[$element_name];
			$markup_string .= $this->additional_element_info[$element_name]['anchor'];
			if($this->use_element_labels && $element->is_labeled())
				$markup_string .=  $this->get_element_name($element);
			$markup_string .= $element->get_comments('before');
			$markup_string .= $element->get_display();
			$markup_string .= $element->get_comments();
			return $markup_string;
		}
	}

	/**
	*  @package disco
	*/
	class ElementStacked extends ElementGroup
	{
		var $type = 'stacked';
		
		function get_display()
		{
			$markup_string = '';
			
			if(!empty($this->order))
				$order = $this->order;
			else
				$order = $this->elements;
			
			foreach ($order as $element_name => $element)
			{
				//if (!$element->is_hidden())
					$markup_string .= '<div id="'.str_replace('_', '', $element_name).'" class="stackedElement">'.$this->get_individual_element_display($element_name).'</div>'."\n";
				//else
				//	$markup_string .= $element->get_display()."\n";
			}
			return $markup_string;
		}
		
		function get_individual_element_display($element_name)
		{
			$markup_string = '';
			$element = $this->elements[$element_name];
			$markup_string .= $this->additional_element_info[$element_name]['anchor'];
			if($this->use_element_labels && $element->is_labeled())
				$markup_string .=  '<label for="'.$element_name.'">'.$this->get_element_name($element).'</label>'."\n";
			$markup_string .= $element->get_comments('before')."\n";
			$markup_string .= $element->get_display()."\n";
			$markup_string .= $element->get_comments()."\n";
			return $markup_string."\n";
		}
	}
	/**
	*  @package disco
	*/
	class ElementTable extends ElementGroup
	{
		var $type = 'table';
		var $span_columns = true;
		var $columns = array();	//column identifier => display name
		var $column_order; //numerical index => column identifier 
		var $rows = array();
		var $row_order;
		var $position = array(); 	//row_identifier => array( column_identifier => element );
		var $elements_in_position = array();
		
		
		function set_columns($headers)
		{
			$this->columns = $headers;
		}
		
		function set_rows ($headers)
		{
			$this->rows = $headers;
		}
		
		function set_column_display_name($column_indicator, $display_name)
		{
			if($this->is_column($column_indicator))
			{
				$this->columns[$column_indicator] = $display_name;
				return true;
			}
			else
			{
				trigger_error('Could not set display name; '.$column_indicator.' is not a column of this element table.', WARNING);
				return false;
			}
		}
	
		function set_row_display_name($row_indicator, $display_name)
		{
			if($this->is_row($row_indicator))
			{
				$this->rows[$row_indicator] = $display_name;
				return true;
			}
			else
			{
				trigger_error('Could not set display name; '.$row_indicator.' is not a column of this element table.', WARNING);
				return false;
			}
		}
	
		function set_position ($element_name, $row_indicator, $column_indicator)
		{
			if(!$this->element_is_in_group($element_name))
				trigger_error(	$element_name.' is not a member of this element table', WARNING );
			elseif(!$this->is_row($row_indicator))
				trigger_error(	$row_indicator.' is not a recognized row of this element table', WARNING );
			elseif(!$this->is_row($column_indicator))
				trigger_error(	$column_indicator.' is not a recognized column of this element table', WARNING );
			
			if(empty($this->position[$row_indicator][$column_indicator]))
			{
				$this->position[$row_indicator][$column_indicator] = $element_name;
				return true;
			}
			else		
			{
				trigger_error('Another element already occupies position ('.$row_indicator.', '.$column_indicator.')', WARNING);
				return false;
			}
		}
		
		function element_has_position($element_name)
		{
			if(in_array($element_name, $this->elements_in_position))
				return true;
			else
				return false;
		}
		
		function free_position ($row_indicator, $column_indicator)
		{
			if(!$this->is_row($row_indicator))
				trigger_error(	$row_indicator.' is not a recognized row of this element table', WARNING );
			if(!$this->is_row($column_indicator))
				trigger_error(	$column_indicator.' is not a recognized column of this element table', WARNING );
				
			if(!empty($this->position[$row_indicator][$column_indicator]))
			{
				$element_name = $this->position[$row_indicator][$column_indicator];
				unset($this->position[$row_indicator][$column_indicator]);
				unset($this->elements_in_position[array_search($element_name)]);	
			}
			
		}
		
		
		function _put_elements_in_position()
		{
			if(!empty($this->order))
				$element_order = $this->order;
			else
				$element_order = array_keys($this->elements);
			foreach($this->row_order as $row)
			{
				foreach($this->column_order as $column)
				{
					//check to make sure this element hasn't already been placed
					while(in_array(current($element_order), $this->elements_in_position))
					{
						next($element_order);
					}
					
					//check to make sure that no elements are occupying this position
					if(empty($this->position[$row][$column]) && current($element_order))
					{
						$this->position[$row][$column] = current($element_order);
						$this->elements_in_position[] = current($element_order);
						next($element_order);
					}					
				}
			}
		}
		
		function set_defaults()
		{
			if(empty($this->columns))
				$this->columns = array('default' => ' ');
			if(empty($this->rows))
			{
				foreach($this->elements as $element_name => $element)
				{
					$this->rows[$element_name] = $element_name;
				}
			}
			
			$this->column_order = array_keys($this->columns);
			$this->row_order = array_keys($this->rows);
		}
		
		function get_display()
		{
			$markup_string = '';
			
			$this->set_defaults();
			$this->_put_elements_in_position();
			
			$markup_string .= '<table>'."\n";
			$markup_string .= $this->get_table_headers();
			$markup_string .= $this->get_table_data();
			$markup_string .= '</table>'."\n";
			return $markup_string;
		}
		
		function get_table_headers()
		{
			$markup_string = '';
			$markup_string .= '<thead><tr>';
			if($this->row_labels_exist())
			{
				$markup_string .=  '<th></th>';
			}
			foreach($this->column_order as $position=>$column)
			{
				if(!empty($this->columns[$column]))
					$name = $this->columns[$column];
				else
					$name = prettify_string($column);
				$class = strtolower(preg_replace('/[^\w]/','', $column)).'Col';
				$markup_string .='<th class="'.$class.'">'.$name.'</th>';
			}
			$markup_string .='</tr></thead>'."\n";
			return $markup_string;
		}
		
		function get_table_data()
		{
			$markup_string = '';
			$markup_string .= '<tbody>'."\n";
			foreach($this->position as $row => $column_array)
			{
				$markup_string .= '<tr>'."\n";
				//display row name, if applicable
				if($this->row_labels_exist())
					$markup_string .= '<th class="rowLabel">'.$this->rows[$row].'</th>'."\n";
				foreach($column_array as $column => $element_name)
				{
					$element = $this->elements[$element_name];
					
					if(!empty($element))
					{
						$class = strtolower(preg_replace('/[^\w]/','', $column)).'Col';
						$markup_string .= '<td class="'.$class.'">'."\n";
						$markup_string .= $this->additional_element_info[$element_name]['anchor']."\n";
						if($this->use_element_labels)
							$markup_string .= $this->get_element_name($element);
						$markup_string .= $element->get_comments('before')."\n";
						$markup_string .= $element->get_display()."\n";
						$markup_string .= $element->get_comments()."\n";
						$markup_string .= '</td>'."\n";			
					}
				}
				$markup_string .= '</tr>'."\n";
			}
			$markup_string .= '</tbody>'."\n";
			return $markup_string;
		}
					
		
		function is_row($row_name)
		{
			if(isset($this->rows[$row_name]))
				return true;
			else
				return false;
		}
		
		function is_column($column_name)
		{
			if(isset($this->columns[$column_name]))
				return true;
			else
				return false;
		}
		
		function row_labels_exist()
		{
			foreach($this->rows as $row_indicator=>$row_display_name)
			{
				if(!empty($row_display_name))
					return true;
			}
		}

	
	}

?>

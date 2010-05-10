<?php

/**
 * Type library for offering selections of one or more options.
 * @package disco
 * @subpackage plasmature
 */

require_once PLASMATURE_TYPES_INC."default.php";

/**
 * The abstract class that powers plasmature types that have multiple options
 * (e.g. radio buttons, selects). The optionType encapsulates a list of
 * possible values with the one selected value.
 * 
 * This is considered an abstract type.
 * 
 * @abstract
 * @package disco
 * @subpackage plasmature
 */
class optionType extends defaultType
{
	var $type = 'option';
	var $type_valid_args = array('options', 'sort_options');
	/**
	 * The possible values of this element.
	 * Format: value as it should be stored => value as it should be displayed.
	 * @var array
	 */
	var $options = array();
	/**
	 * True if the {@link options} array should be sorted into ascending order.
	 * Otherwise, options will be displayed in the order that they were added to the {@link options} array.
	 */
	var $sort_options = true;
	/**
	 *  Loads default options defined using {@link load_options()} and sorts the {@link options} array if {@link sort_options} is true.
	 */
	function additional_init_actions($args = array())
	{
		parent::additional_init_actions($args);
		if( isset($this->options) )
		{
			$this->set_options( $this->options );
		}
		$this->load_options();
		if($this->sort_options)
			asort( $this->options );
	}
	/**
	 *  Hook for child classes that have a default set of options (e.g. {@link stateType}, {@link languageType}).
	 */
	function load_options( $args = array() )
	{
	}
	function set_options( $options )
	{
		if ( is_array( $options ) )
			$this->options = $options;
		else
		{
			trigger_error('Could not set options for element '.$this->name.'; '.$this->type.'::set_options() requires an array as an argument', WARNING);
			return false;
		}
	}
	/**
	 * Sets display order of options.
	 * @param $order array Array of options in the order you want them to appear.
	 *
	 * @todo Should this be disabled if {@link sort_options} is true?
	 */
	function set_option_order( $order )
	{
		if(!empty($this->options))
		{
			if( is_array( $order ) )
			{
				$options = $this->options;
				$new_options = array();
				foreach($order as $option_key)
				{
					if( isset( $this->options[ $option_key ] ) )
					{
						$new_options[ $option_key ] = $this->options[ $option_key ];
						unset( $options[ $option_key ] );
					}
				}
				foreach($options as $key => $display_name)
					$new_options[ $key ] = $display_name;
				$this->options = $new_options;
			}
		}
		else
			trigger_error('Could not set option order; '.$this->name.' does not have any options set.');
	}
	
	/**
	 * Returns the value as it was displayed in the element -- e.g., if you store
	 * departments as abbreviations but display them in the option element as
	 * department names, will return the department name that corresponds to the
	 * internal value. -- MG 2006/07
	 */
	function get_value_for_display()
	{
		if(!empty($this->value) && !empty($this->options[$this->value]))
			return $this->options[$this->value];
		else
			return false;
	}
}

/**
 * Powers plasmature types that have multiple options (e.g. radio buttons, select drop-downs).
 * This is the same as the {@link optionType} but leaves options unsorted.
 * @package disco
 * @subpackage plasmature
 * @deprecated Use {@link optionType} instead and set {@link sort_options} to false.
 */
class option_no_sortType extends optionType
{
	var $type = 'option_no_sort';
	var $sort_options = false;
}

/**
 * Displays {@link options} as radio buttons.
 * @package disco
 * @subpackage plasmature
 */
class radioType extends optionType
{
	var $type = 'radio';
	function get_display()
	{
		$i = 0;
		$str = '<div id="'.$this->name.'_container" class="radioButtons">'."\n";
		$str .= '<table border="0" cellpadding="1" cellspacing="0">'."\n";
		foreach( $this->options as $key => $val )
		{
			$id = 'radio_'.$this->name.'_'.$i++;
			$str .= '<tr>'."\n".'<td valign="top"><input type="radio" id="'.$id.'" name="'.$this->name.'" value="'.$key.'"';
			if ( $key == $this->value )
				$str .= ' checked="checked"';
			$str .= '></td>'."\n".'<td valign="top"><label for="'.$id.'">'.$val.'</label></td>'."\n".'</tr>'."\n";
		}
		$str .= '</table>'."\n";
		$str .= '</div>'."\n";
		return $str;
	}
}

/**
 * Same as {@link radioType} radio buttons, but inline.
 * @package disco
 * @subpackage plasmature
 */
class radio_inlineType extends optionType
{
	var $type = 'radio_inline';

	function get_display()
	{
		$i = 0;
		$str = '<div id="'.$this->name.'_container" class="radioButtons inlineRadioButtons">'."\n";
		foreach( $this->options as $key => $val )
		{
			$id = 'radio_'.$this->name.'_'.$i++;
			$str .= '<span class="radioItem"><span class="radioButton"><input type="radio" id="'.$id.'" name="'.$this->name.'" value="'.$key.'"';
			if ( $key == $this->value )
				$str .= ' checked="checked"';
			$str .= '></span> <label for="'.$id.'">'.$val.'</label></span> '."\n";
		}
		$str .= '</div>'."\n";
		return $str;
	}
}

/**
 * Same as {@link radioType}, but doesn't sort {@link options}.
 * @package disco
 * @subpackage plasmature
 */
class radio_no_sortType extends radioType
{
	var $type = 'radio_no_sort';
	var $sort_options = false;
}

/**
 * Same as {@link radio_inlineType}, but doesn't sort {@link options}.
 * @package disco
 * @subpackage plasmature
 */
class radio_inline_no_sortType extends radio_inlineType
{
	var $type = 'radio_inline_no_sort';
	var $sort_options = false;
	
}

/**
 * Displays {@link options} as radio buttons, adding an "Other" freetext option at the end.
 * @package disco
 * @subpackage plasmature
 */
class radio_with_otherType extends optionType
{
	var $type = 'radio_with_other';
	var $other_label = 'Other: ';
	var $type_valid_args = array( 'other_label' );
	function get_display()
	{
		$i = 0;
		$str = '<div id="'.$this->name.'_container" class="radioButtons">'."\n";
		$str .= '<table border="0" cellpadding="1" cellspacing="0">'."\n";
		$checked = false;
		foreach( $this->options as $key => $val )
		{
			$id = 'radio_'.$this->name.'_'.$i++;
			$str .= '<tr>'."\n".'<td valign="top"><input type="radio" id="'.$id.'" name="'.$this->name.'" value="'.$key.'"';
			if ( $key == $this->value ) {
				$str .= ' checked="checked"';
				$checked = true;
			}
			$str .= '></td>'."\n".'<td valign="top"><label for="'.$id.'">'.$val.'</label></td>'."\n".'</tr>'."\n";
		}
		$id = 'radio_'.$this->name.'_'.$i++;
		$str .= '<tr>'."\n".'<td valign="top"><input type="radio" id="'.$id.'" name="'.$this->name.'" value="other"';
		if ( !$checked && $this->value)
		{
			$other_value = $this->value;
			$str .= ' checked="checked"';
		} else {
			$other_value = '';
		}
		$str .= '></td>'."\n".'<td valign="top"><label for="'.$id.'">'.$this->other_label.'</label>';
		$str .= '<input type="text" name="'.$this->name.'_other" value="'.str_replace('"', '&quot;', $other_value).'"  /></td>'."\n".'</tr>'."\n";
		$str .= '</table>'."\n";
		$str .= '</div>'."\n";
		return $str;
	}
	function grab_value()
	{
		$return = NULL;
		$http_vars = $this->get_request();
		if ( isset( $http_vars[ $this->name ] ) )
		{
			$return = trim($http_vars[ $this->name ]);
			if ($return == 'other')
			{
				if ( isset( $http_vars[ $this->name .'_other' ] ) )
					$return = trim($http_vars[ $this->name .'_other' ]);
				else
					$return = '';
			}
		}
		return $return;
	}
}

/**
 * Same as {@link radio_with_otherType}, but doesn't sort {@link options}.
 * @package disco
 * @subpackage plasmature
 */
class radio_with_other_no_sortType extends radio_with_otherType
{
	var $type = 'radio_with_other_no_sort';
	var $sort_options = false;
}

/**
 * Displays {@link options} as a group of checkboxes.
 * Use {@link checkboxType} to create a single checkbox.
 * @package disco
 * @subpackage plasmature
 */
class checkboxgroupType extends optionType
{
	var $type = 'checkboxgroup';
	function get_display()
	{
		$str = '<div class="checkBoxGroup">'."\n";
		$str .= '<table border="0" cellpadding="1" cellspacing="0">'."\n";
		$i = 0;
		foreach( $this->options as $key => $val )
		{
			$id = 'checkbox_'.$this->name.'_'.$i;
			$str .= '<tr><td valign="top"><input type="checkbox" id="'.$id.'" name="'.$this->name.'['.$i.']" value="'.htmlspecialchars($key, ENT_QUOTES).'"';
			if ( is_array($this->value) ) {
				if ( array_search($key, $this->value) !== false )
					$str .= ' checked="checked"';
			}
			else {
				if ( $key == $this->value )
					$str .= ' checked="checked"';
			}
			$str .= ' /></td><td valign="top"><label for="'.$id.'">'.$val."</label></td></tr>\n";
			$i++;
		}
		$str .= '</table>'."\n";
		$str .= '</div>'."\n";
		return $str;
	}
	function grab()
	{
		// Without this condition, if the user unchecks all the
		// boxes, the default value will be used. The reason is
		// that if no checkboxes are checked, the browser sends no
		// post variable at all for the group. This is in contrast
		// to other form elements, e.g., input boxes, for which
		// the browser sends an empty string if the user doesn't
		// enter anything. And the default plasmature behavior
		// assumes that all the form elements will behave like the
		// input boxes. So what happens is that $this-value is
		// first set to the default value, and then over written
		// by the request variable if one is present (if an
		// appropriate request variable isn't present, it's
		// assumed that it's the first time). But in the case of
		// checkboxes, this is a false assumption, because (as I
		// said above) if no box is checked, no request variable
		// is sent. --NF 23/Feb/2004
		$request = $this->get_request();
		if ( isset($request[ $this->name ]) )
			$this->set( $request[ $this->name ] );
		else
			$this->set( array() );
	}
	function get()
	{
		// This conditional is needed because when disco checkes
		// whether values have been provided for all required
		// variables, disco simply (and naively, I might add)
		// evaluates the boolean value of $this->get(), rather
		// than the boolean value of, say
		// !empty($this->get()). (It might be worth fixing this
		// behavior of disco, but I'm not sure at this point
		// whether to do so would break anything.) But, in
		// contrast to an empty string, a variable which points to
		// an empty array evaluates to true. So I check for that
		// here. --NF 23/Feb/2004
		if ( empty($this->value) )
			return false;
		else
			return $this->value;
	}
	function get_cleanup_rule()
	{
		return array( 'function' => 'turn_into_array' );
	}
}

/**
 * Like {@link checkboxgroupType}, but doesn't sort options.
 * @package disco
 * @subpackage plasmature
 */
class checkboxgroup_no_sortType extends checkboxgroupType
{
	var $type = 'checkboxgroup_no_sort';
	var $sort_options = false;
}

/**
 * @package disco
 * @subpackage plasmature
 * @todo do better data checking to make sure that value is one of the available options (or empty/null)
 */
class selectType extends optionType
{
	var $type = 'select';
	var $type_valid_args = array(	'n' => 'size',
									'multiple',
							     	'add_null_value_to_top');
	var $n = 1;
	/**
	 *  True if multiple options may be selected.
	 * @var boolean
	 */
	var $multiple = false;
	/**
	 * If true, adds a null value to the top of the select.
	 */
	var $add_null_value_to_top = true;
	/* function grab()
	{
		parent::grab();
		$value = $this->get();
		if(is_array($value))
		{
			foreach($value as $key => $val)
			{
				if(!isset($this->options[$key]))
				{
					$this->set_error(htmlspecialchars($key,ENT_QUOTES).' is not an acceptable value');
				}
			}
		}
		elseif(is_string($value))
		{
			if(!isset($this->options[$value]))
			{
				$this->set_error(htmlspecialchars($value,ENT_QUOTES).' is not an acceptable value');
			}
		}
		else
		{
			$this->set_error('Strange problem');
		}
	} */
	function get_display()
	{
		$str = '<select id="'.$this->name.'Element" name="'.$this->name.'" size="'.$this->n.'" '.($this->multiple ? 'multiple="multiple"' : '').'>'."\n";
		if($this->add_null_value_to_top)
			$str .= '<option value="" '.(empty($this->value)?'selected="selected"':'').'>--</option>'."\n";
		foreach( $this->options as $key => $val )
		{
			if( $val === '--' )
			{
				$str .= '<option value="">--</option>' . "\n";
			}
			else
			{
				$str .= '<option value="'.$key.'"';
				if ( is_array($this->value) ) {
					if ( array_search($key, $this->value) !== false )
						$str .= ' selected="selected"';
				}
				else {
					if ( $key == $this->value )
						$str .= ' selected="selected"';
				}
				$str .= '>'.$val.'</option>'."\n";
			}
		}
		$str .= '</select>'."\n";
		return $str;
	}
}

/**
 * Same as {@link selectType}  but doesn't sort the {@link options}.
 * @package disco
 * @subpackage plasmature
 */
class select_no_sortType extends selectType
{
	var $type = 'select_no_sort';
	var $add_null_value_to_top = false;
	var $sort_options = false;
}	
	 
/**
 * @package disco
 * @subpackage plasmature
 */
class select_no_labelType extends selectType // {{{
{
	var $_labeled = false;
}

/**
 * @package disco
 * @subpackage plasmature
 */
class file_listerType extends selectType
{
	var $type = 'file_lister';
	var $type_valid_args = array( 'extension',
								  'strip_extension',
								  'prettify_file_name',
								  'directory');
	function load_options( $args = array())
	{
		$files = array();
		if ( isset( $this->directory ) )
		{
			$handle = opendir( $args['directory'] );
			while( $entry = readdir( $handle ) )
			{
				if( is_file( $this->directory.$entry ) )
				{
					$show_entry = true;
					$entry_display = $entry_value = $entry;
					if( !empty( $this->strip_extension ) )
						$entry_display = $entry_value = substr( $entry, 0, strrpos( $entry, '.' ));
					if( !empty( $this->prettify_file_name ) )
						$entry_display = prettify_string( substr( $entry, 0, strrpos( $entry, '.' ) ) );
					if( !empty( $this->extension ) )
						if ( !preg_match( '/'.$this->extension.'$/',$entry ) )
							$show_entry = false;
					if( $show_entry )
						$files[ $entry_value ] = $entry_display;
				}
			}
			ksort( $files );
		}
		$this->options += $files;
	}
}

/**
 * Presents options in a select element after loading them from a table.
 * @package disco
 * @subpackage plasmature
 */
class tablelinkerType extends selectType
{
	var $type = 'tablelinker';
	var $table;
	var $type_valid_args = array('table');
	function load_options( $args = array() )
	{
		// see if table is set
		if ( !isset( $this->table ) OR empty( $this->table ) )
			// if not, check the name of the element
			if ( preg_match( '/^(.*)_id$/', $this->name, $matches ) )
				// if it is tablename_id, we are good
				$this->table = $matches[ 1 ];
			// bad stuff.  we need a valid table name
			else
			{
				trigger_error( 'dblinkerType::init - no valid tablename found' );
				return;
			}
		// load options from DB
		$q = "SELECT id, name FROM ".$this->table;
		$r = mysql_query( $q ) OR die( 'Plasmature Error :: tablelinkerType :: "'.$q.'" :: '.mysql_error() );
		while( $row = mysql_fetch_array( $r, MYSQL_ASSOC ) )
			$options[ $row['id'] ] = $row['name'];
		// sort options by value maintaining key association
		asort( $options );
		$this->set_options( $options );
	}
}

/**
 * Displays a drop-down of numbers within the given range.
 * Begins at {@link start} (default 0) and stops after {@link end} (default 10), using {@link step} (default 1) to increment.
 * @package disco
 * @subpackage plasmature
 */
class numrangeType extends selectType
{
	var $type = 'numrange';
	var $start = 0;
	var $end = 10;
	var $step = 1;
	var $empty_option = false;
	var $type_valid_args = array ( 'start',
								   'end',
								   'step',
								   'empty_option',
								  );
	function load_options( $args = array() )
	{
		$this->options = array();
		$this->add_numrange_to_options();
	}
	function add_numrange_to_options()
	{
		for( $i = $this->start; $i <= $this->end; $i += $this->step )
			$this->options[ $i ] = $i;
	}
}
/**
 * Displays a drop-down of ages.
 * Exactly like {@link numrangeType} except that it displays '0-1' instead of '0' and won't display negative numbers.
 * @package disco
 * @subpackage plasmature
 */
class ageType extends numrangeType
{
	var $type = 'age';
	function load_options( $args = array() )
	{
		if( $this->start < 0 )
			$this->start = 0;
		if( $this->start == 0 )
		{
			$this->options[ '0-1' ] = '0-1';
			$this->start = 1;
		}
		$this->add_numrange_to_options();
	}
}

/**
 * @package disco
 * @subpackage plasmature
 */
class select_multipleType extends selectType
{
	var $type = 'select_multiple';
	var $type_valid_args = array( 'select_size',
								  'multiple_display_type'
								);
	/** Can be 'checkbox' or 'select' to use multiple checkboxes or a multiple select box, respectively*/
	var $multiple_display_type = 'select';
	/** if using multiple select box, how many rows to show at once? */
	var $select_size = 8;
	/*function init( $args = '' )
	{
		if ( !empty( $args ) )
		{
			if ( isset( $args['multiple_display_type'] ) )
				$this->multiple = $args['multiple_display_type'];
		}
		parent::init( $args );
	} */
	function array_contains( $array, $value )
	{
		if(!is_array( $array ) )
			return false;
		foreach( $array as $item )
			if($item == $value)
				return true;
		return false;
	}
	function nOptions()
	{
		$i = 0;
		foreach( $this->options as $key =>$val )
			$i++;
		return $i;
	}
	function get_display()
	{
		$str = '';
		if( $this->multiple_display_type == 'checkbox' )
		{
			foreach( $this->options as $key => $val )
			{
				$str .= "\n".'<input type="checkbox" id="'.$this->name.'-'.$key.'" name="'.$this->name.'[]" value="'.$key.'"';
				if( $this->array_contains( $this->value,$key ) )
					$str .= ' checked="checked"';
				$str .= ' />'."\n".'<label for="'.$this->name.'-'.$key.'">'.$val.'</label><br />';
			}
			$str .= "\n";
		}
		else
		{
			$str = '<select name="'.$this->name.'[]" multiple="multiple" size="'.$this->select_size.'">'."\n";
			//$str .= '<option value="">*none*</option>'."\n";
			foreach( $this->options as $key => $val )
			{
				$str .= '<option value="'.$key.'"';
				if ( $this->array_contains( $this->value, $key) )
					$str .= ' selected="selected"';
				$str .= '>'.$val.'</option>'."\n";
			}
			$str .= '</select>'."\n";
		}
		$str .= "\n";
		return $str;
	}
}

/**
 * @package disco
 * @subpackage plasmature
 */
class select_jsType extends selectType
{
	var $type = 'select_js';
	var $script_url = '';
	var $type_valid_args = array('n' => 'size', 'multiple', 'script_url',
		'add_null_value_to_top');
	
	function get_display()
	{
		$str  = $this->script_tag();
		$str .= parent::get_display();
		return $str;
	}
	
	function script_tag()
	{
		$s = '';
		if ( !empty( $this->script_url ) )
			$s = '<script language="JavaScript" src="'.$this->script_url.'"></script>'."\n";
		return $s;
	}
}

/**
 * Same as {@link select_jsType} but doesn't sort options.
 * @package disco
 * @subpackage plasmature
 */
class select_no_sort_jsType extends select_jsType
{
	var $type = 'select_no_sort_js';
	var $sort_options = false;
}

/**
 * @package disco
 * @subpackage plasmature
 */
class sidebar_selectType extends selectType
{
	var $type = 'sidebar_select';
	var $page = 'print_sidebar.php3?id=';
	var $frame = 'oIframe';
	function get_display()
	{
		$str = '<select name="'.$this->name.'" onChange="document.all.'.$this->frame.'.src=\''.$this->page.'\'
			+ this.form.'.$this->name.'.options[this.form.'.$this->name.'.selectedIndex].value">'."\n";
		foreach( $this->options as $key => $val )
		{
			$str .= '<option value="'.$key.'"';
			if ( $key == $this->value )
				$str .= ' selected="selected"';
			$str .= '>'.$val.'</option>'."\n";
		}
		$str .= '</select>'."\n";
		return $str;
	}
}

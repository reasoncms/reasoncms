<?
include_once('paths.php'); 
// require_once PLASMATURE_TYPES_INC."default.php";
require_once PLASMATURE_TYPES_INC.'options.php';
// include_once(DISCO_INC.'plasmature/plasmature.php');

/**
* radio type for giving form designations
*/
class designationsType extends radio_with_other_no_sortType
{
    var $naa_opts = array('General',
        'Baseball',
        'Basketball (men)',
        'Basketball (women)',
        'Cross Country (men)',
        'Cross Country (women)',
        'Football',
        'Golf (men)',
        'Golf (women)',
        'Soccer (men)',
        'Soccer (women)',
        'Softball',
        'Swimming & Diving (men)',
        'Swimming & Diving (women)',
        'Tennis (men)',
        'Tennis (women)',
        'Track & Field (men)',
        'Track & Field (women)',
        'Volleyball',
        'Wrestling'
    );

    var $type = 'designations';
    // var $type_valid_args = array( 'sub_labels' );
    // var $sub_labels = array();
    protected function _array_val_ok()
    {
        return false;
    }
    function register_fields(){
        return array('designation', 'norse_atheltic_association_details');
    }
    function get_display()
    {
        $i = 0;
        $str = '<div id="'.$this->name.'_container" class="radioButtons">'."\n";
        echo "Value = {$this->value}";
        if($this->add_empty_value_to_top)
        {
            $str .= $this->_get_radio_row('','--',$i++);
        }
        foreach( $this->options as $key => $val )
        {
            if (!empty($this->sub_labels[$key])) $str .= $this->_get_sub_label_row($key);
            $str .= $this->_get_radio_row($key,$val,$i++);
            if ( $this->_is_current_value($key) )
                $checked = true;
        }
        $id = 'radio_'.$this->name.'_'.$i++;
        $str .= '<div class="radioItem"<span class="radioButton"><input type="radio" id="'.$id.'" name="'.$this->name.'" value="Other"';
        if ( $this->_is_current_value($key) )
        {
            // $other_value = $this->value;
            $str .= ' checked="checked"';
        } else {
            $other_value = '';
        }
        $str .= '></span><label for="'.$id.'">'.$this->other_label.'</label>'."\n";
        if(empty($this->other_options))
        {
            $str .= '<input type="text" name="'.$this->name.'_other"/>';
        }
        $str .= '</div>'."\n";
        return $str;
    }
    protected function _get_radio_row($key,$val,$count)
    {
        $str = '';
        $id = 'radio_'.$this->name.'_'.$count;
        $str .= '<div class="radioItem"<span class="radioButton"><input type="radio" id="'.$id.'" name="'.$this->name.'" value="'.htmlspecialchars($key, ENT_QUOTES).'"';
        if ( $this->_is_current_value($key) )
            $str .= ' checked="checked"';
        if ( $this->_is_disabled_option($key) )
            $str .= ' disabled="disabled"';
        $str .= '></span><label for="'.$id.'">'.$val.'</label>';
        if ( $key == 'Norse Athletic Association' ) {
            $str .= '<select id="norse_athletic_association_detailsElement" name="norse_atheltic_association_details">'."\n";
            $request = $this->get_request();
            pray($request);
            echo "<hr>";
            pray($this);
            foreach ($this->naa_opts as $k => $v) {
                $selected = ($v == $request['norse_atheltic_association_details']) ? ' selected="selected"' : '';
                $str .= '<option value="NAA_'.$v.'"'.$selected.'>'.$v.'</option>'."\n";
            }
            $str .= '</select>'."\n";
        }
        $str .= '</div>'."\n";
        return $str;
    }
    
    protected function _get_sub_label_row($key)
    {
        if (!empty($this->sub_labels[$key]))
            return '<tr class="sublabel">'."\n".'<td colspan="2">'.$this->sub_labels[$key].'</td>'."\n".'</tr>'."\n";
    }
}
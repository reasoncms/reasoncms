<?php
include_once('paths.php');
// require_once PLASMATURE_TYPES_INC."default.php";
require_once PLASMATURE_TYPES_INC.'options.php';
// include_once(DISCO_INC.'plasmature/plasmature.php');

/**
* radio type for giving form designations
*/
class designationsType extends radio_with_other_no_sortType
{
    var $type = 'designations';
    var $other_label = 'Other: ';
    var $other_options = array();
    var $type_valid_args = array( 'other_label', 'other_options' , 'naa_options');
    protected function _array_val_ok()
    {
        return false;
    }
    function get_display()
    {
        $i = 0;
        $str = '<div id="'.$this->name.'_container" class="radioButtons">'."\n";
        $checked = false;

        foreach( $this->options as $key => $val )
        {
            $str .= $this->_get_radio_row($key,$val,$i++);
            if ( $this->_is_current_value($key) || array_key_exists($this->value, $this->naa_options))
                $checked = true;
        }
        $id = 'radio_'.$this->name.'_'.$i++;
        $str .= '<div class="radioItem radioItemOther">'."\n";
        $str .= '<span class="radioButton"><input type="radio" id="'.$id.'" name="'.$this->name.'" value="__other__"';
        if ( !$checked && $this->value)
        {
            $other_value = $this->value;
            $str .= ' checked="checked"';
        } else {
            $other_value = '';
        }
        $str .= '></span>'."\n".'<label for="'.$id.'">'.$this->other_label.'</label>';
        if(empty($this->other_options))
        {
            $str .= '<input type="text" name="'.$this->name.'_other" id="'.$this->name.'_otherElement" value="'.str_replace('"', '&quot;', $other_value).'"  />';
        }
        else
        {
            $str .= '<select name="'.$this->name.'_other" id="'.$this->name.'_otherElement" class="other">';
            foreach($this->other_options as $k => $v)
            {
                $selected = ($k == $other_value) ? ' selected="selected"' : '';
                $str .= '<option value="'.htmlspecialchars($k, ENT_QUOTES).'"'.$selected.'>'.strip_tags($v).'</option>'."\n";
            }
            $str .= '</select>';
        }
        $str .= '</div>'."\n";
        $str .= '</div>'."\n";
        return $str;
    }
    protected function _get_radio_row($key,$val,$count)
    {
        $str = '';
        $checked = false;
        $id = 'radio_'.$this->name.'_'.$count;
        $str .= '<div class="radioItem">'."\n";
        $str .= '<span class="radioButton"><input type="radio" id="'.$id.'" name="'.$this->name.'" value="'.htmlspecialchars($key, ENT_QUOTES).'"';
        if ( $this->_is_current_value($key) )
        {
            $str .= ' checked="checked"';
            $checked = true;
        }
        $req = $this->get_request();
        $str .= ' /></span>'."\n".'<label for="'.$id.'">'.$val.'</label>'."\n";
        if ( isset($req['split_gift']) && $key === 'norse_athletic_association') {
            $str .= '<select name="'.$this->name.'_naa_details" id="'.$this->name.'_naa_detailsElement" class="naa_details">';
            foreach ($this->naa_options as $k => $v) {
                if ( $k == $this->value && $checked ) {
                    $selected = ' selected="selected"';
                } else {
                    $selected = '';
                }
                $str .= '<option value="'.htmlspecialchars($k, ENT_QUOTES).'"'.$selected.'>'.strip_tags($v).'</option>'."\n";
            }
            $str .= '</select>'."\n";
        }
        elseif ( $key === 'norse_athletic_association' ){
            $str .= '<select name="'.$this->name.'_naa_details" id="'.$this->name.'_naa_detailsElement" class="naa_details">';
            foreach ($this->naa_options as $k => $v) {
                if ( $k == $this->value && $checked ) {
                    $selected = ' selected="selected"';
                } else {
                    $selected = '';
                }
                $str .= '<option value="'.htmlspecialchars($k, ENT_QUOTES).'"'.$selected.'>'.strip_tags($v).'</option>'."\n";
            }
            $str .= '</select>'."\n";
        }
        $str .= '</div>'."\n";
        return $str;
    }
    function grab_value()
    {
        $return = parent::grab_value();
        $http_vars = $this->get_request();
        $splits = json_decode($http_vars['split_designations']);
        if ( isset($http_vars['split_gift']) && isset($splits->norse_athletic_association)) {
            if ( isset( $http_vars[ $this->name .'_naa_details' ] ) )
            {
                $return = trim($http_vars[ $this->name .'_naa_details' ]);
                if(!empty($this->naa_options) && !isset($this->naa_options[$return]))
                    $this->set_error(strip_tags($this->display_name).': Please choose a value other than "'.htmlspecialchars($return,ENT_QUOTES).'".');
                if($this->_is_disabled_option($return) && !$this->_is_current_value($return))
                    $this->set_error(strip_tags($this->display_name).': Please choose a value other than "'.htmlspecialchars($return,ENT_QUOTES).'".');
            }
            else
                $return = NULL;
        } elseif ( $return == 'norse_athletic_association' ) {
            if ( isset( $http_vars[ $this->name .'_naa_details' ] ) )
            {
                $return = trim($http_vars[ $this->name .'_naa_details' ]);
                if(!empty($this->naa_options) && !isset($this->naa_options[$return]))
                    $this->set_error(strip_tags($this->display_name).': Please choose a value other than "'.htmlspecialchars($return,ENT_QUOTES).'".');
                if($this->_is_disabled_option($return) && !$this->_is_current_value($return))
                    $this->set_error(strip_tags($this->display_name).': Please choose a value other than "'.htmlspecialchars($return,ENT_QUOTES).'".');
            }
            else
                $return = NULL;
        }

        return $return;
    }

    protected function _validate_submitted_value($value)
    {
        if('__other__' == $value)
        {
            return true;
        } elseif ('norse_athletic_association' == $value)
        {
            return true;
        }
        return parent::_validate_submitted_value($value);
    }
    function set( $value )
    {
        $this->value = $value;
    }

    /**
      * Make sure the other value is visible in the request
      **/
    function get_cleanup_rules()
    {
        return array(
            $this->name => array('function' => 'turn_into_string' ),
            $this->name . '_other' => array('function' => 'turn_into_string' ),
            $this->name . '_naa_details' => array('function' => 'turn_into_string'),
            );
    }

    protected function _is_current_value($value, $report = false)
    {
        if($report) echo $value.' :: '.$this->value.' → ';
        if(!isset($this->value) && NULL !== $value )
            return false;
        if(is_array($this->value))
        {
            if($report) echo in_array( (string) $value, $this->value ).'<br />';
            return in_array( (string) $value, $this->value );
        }
        else
        {
            if ($value === 'norse_athletic_association' && array_key_exists($this->value, $this->naa_options))
                return true;
            if($report) echo ( (string) $value == (string) $this->value ).'<br />';
            return ( (string) $value == (string) $this->value );
        }
    }
}

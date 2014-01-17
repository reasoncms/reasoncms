<?php
/**
 * @package disco
 * @author Nick Jones
 * 
 */
	/**
	 * Include a bunch of stuff relating to Disco forms
	 */
	include_once( 'paths.php');
	include_once( CARL_UTIL_INC . 'dev/pray.php' );
	include_once( CARL_UTIL_INC . 'basic/misc.php' );
	include_once( CARL_UTIL_INC . 'basic/cleanup_funcs.php' );
	include_once( CARL_UTIL_INC . 'dev/debug.php' );
	include_once( CARL_UTIL_INC . 'error_handler/error_handler.php' );
	include_once( DISCO_INC . 'plasmature/plasmature.php' );
	include_once( DISCO_INC . 'boxes/boxes.php' );
	include_once( DISCO_INC . 'disco.php' );
	
	/**
	 * Class to support character limiting in disco forms -- will be added as a callback within Disco
	 */
	
	class DiscoInputLimiter
	{
	    /**
	     * The disco form containing the fields to limit
	     * @var object
	     */
	    private $disco_form;
	    
	    /**
	     * An array containing field => character limit
	     * @var array
	     */
	    private $field_limits = array();

	    /**
	     * An array containing field => suggested character limit
	     * @var array
	     */
	    private $suggested_field_limits = array();

	    /**
	     * Whether or not we auto hide and show the warnings.
	     * 
	     * Call auto_show_hide(false) if you want warnings to always show.
	     *
	     * @var array
	     */
	    private $auto_show_hide = array();
	        
	    /**
	     * Disco form must be passed in -- callbacks will be attached to various process points in 
	     * disco 
	     * @param $disco_form The Disco form containing fields whose characters should be limited
	     */
	    public function __construct($disco_form)
	    {
	        $this->disco_form = $disco_form;
	        $this->disco_form->add_callback(array($this, 'check_field_lengths'), 'run_error_checks');
	        $this->disco_form->add_callback(array($this, 'include_js'), 'pre_show_form');
	        $this->disco_form->add_callback(array($this, 'add_limiter_comments'), 'on_every_time');
	    }
	    
	    /**
	     * Includes the complimentary javascript file -- is there a better way to do this?
	     * 
	     */
	    public function include_js()
	    {
	        echo '<script src="'. REASON_PACKAGE_HTTP_BASE_PATH . 'disco/plugins/input_limiter/input_limiter.js' .'"></script>';
	    }
	    
	    
	    /**
	     * Sets a character limit on the given field. 
	     * For backwards compatibility, the actual limit is calculated as the maximum between
	     * $char_limit and whatever is currently stored in the database for the given element
	     * @param string $field_name -- the field/element to limit
	     * @param int $char_limit -- the number of chars to limit to
	     */
	    public function limit_field($field_name, $char_limit)
	    {
	        $element_value = $this->disco_form->get_value($field_name);
	        $current_num_chars = carl_util_count_html_text_characters($element_value);
	        $max_chars = max($current_num_chars, $char_limit);
	        $this->field_limits[$field_name] = $max_chars;
	    }
	    
	    /**
	     * Suggests a character limit for a given field.
	     *
	     * @param string $field_name the field/element to suggest a limit for.
	     * @param int $char_limit the number of characters for the suggested limit.
	     */
	    public function suggest_limit($field_name, $char_limit)
	    {
	    	$this->suggested_field_limits[$field_name] = $char_limit;
	    }
	    
	    /**
	     * Get / set whether or not the input limit text for a field should auto show/hide.
	     *
	     * @param string $field_name the field/element to suggest a limit for.
	     * @param int $char_limit the number of characters for the suggested limit.
	     */
	    public function auto_show_hide($field_name, $boolean = NULL)
	    {
	    	if ($boolean !== NULL)
	    	{
	    		$this->auto_show_hide[$field_name] = $boolean;
	    	}
	    	return (isset($this->auto_show_hide[$field_name])) ? $this->auto_show_hide[$field_name] : true;
	    }
	    
	    /**
	     * Adds comment to each element whose characters are being limited. 
	     * These comments will be wrapped in a <span> for easy access by js
	     *
	     */
	    public function add_limiter_comments()
	    {
	        foreach($this->field_limits as $field => $char_limit)
	        {
	            // grab value from DB, count its characters, calculate how many remain
	            $element_value = $this->disco_form->get_value($field);
	            $chars_remaining = $char_limit - carl_util_count_html_text_characters($element_value);
	            
	            $auto_show_hide = ($this->auto_show_hide($field)) ? ' autoShowHide' : '';
	            $formatted_char_limit = '<div class="smallText showRemaining inputLimitNote' . $auto_show_hide . '" style="display: none;">
	                Characters remaining: <span class="charsRemaining">'. $chars_remaining . '</span>
                    <span class="charLimit", style="display: none;">'. $char_limit . '</span></div>';
                
                $over_limit_note = '<div class="smallText overLimitNote' . $auto_show_hide . '" style="display: none; font-weight:bold;">
                <span class="numCharsOver">0</span> characters over the limit! Please shorten text.</b></div>';
                
                $this->disco_form->add_comments($field, $formatted_char_limit);
                $this->disco_form->add_comments($field, $over_limit_note);
	        }
	        foreach($this->suggested_field_limits as $field => $char_limit)
	        {
	            // grab value from DB, count its characters, calculate how many remain
	            $element_value = $this->disco_form->get_value($field);
	            $chars_remaining = $char_limit - carl_util_count_html_text_characters($element_value);
	            
	            $auto_show_hide = ($this->auto_show_hide($field)) ? ' autoShowHide' : '';
	            $formatted_char_limit = '<div class="smallText inputLimitNote' . $auto_show_hide . '" style="display: none;">
	                Characters remaining: <span class="charsRemaining">'. $chars_remaining . '</span>
                    <span class="charLimit", style="display: none;">'. $char_limit . '</span></div>';
                
                $over_limit_note = '<div class="smallText overLimitNote' . $auto_show_hide . '" style="display: none; font-weight:bold;">
                <span class="numCharsOver">0</span> characters over the suggested limit! Consider shortening text.</b></div>';
                
                $this->disco_form->add_comments($field, $formatted_char_limit);
                $this->disco_form->add_comments($field, $over_limit_note);
	        }
	    }
	    
	    /**
	     * Checks the lengths of each field whose characters were supposed to be limited
	     * Sets a disco error if the maximum number of characters was exceeded
	     */
	    
	    public function check_field_lengths()
	    {
	        foreach($this->field_limits as $field => $char_limit)
	        {
	            $num_chars = carl_util_count_html_text_characters($this->disco_form->get_element_property($field, 'value'));
	            $chars_over_limit = $num_chars - $char_limit;
	            if($chars_over_limit > 0)
	            {
	                $field_display_name = $this->disco_form->get_display_name($field);
	                $this->disco_form->set_error($field, 
	                'There are '. $chars_over_limit .' more characters than allowed in "'. $field_display_name . 
	                '". Please shorten the text in this field.');
	            }
	        }
	    }
	    
	}
?>
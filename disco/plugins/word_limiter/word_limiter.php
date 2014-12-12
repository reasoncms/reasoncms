<?php
/**
 * @package disco
 * @author Tom Feiler
 *
 * based largely on how DiscoInputLimiter (which tracks characters) works.
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
	
	class DiscoWordLimiter
	{
		// we use a regex to count the words. We'll also supply this to the javascript side of the
		// fence. This ensures we get consistent results on both client and server without constant
		// talking back and forth.
		private $wordCountRegex = "/\S+/";

	    /**
	     * The disco form containing the fields to limit
	     * @var object
	     */
	    private $disco_form;
	    
	    /**
	     * An array containing field => word limit
	     * @var array
	     */
	    private $field_limits = array();

	    /**
	     * An array containing field => suggested word limits
	     * @var array
	     */
	    // private $suggested_field_limits = array();

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
	        $this->disco_form->add_callback(array($this, 'check_field_wordcounts'), 'run_error_checks');
	        $this->disco_form->add_callback(array($this, 'include_js'), 'pre_show_form');
	        $this->disco_form->add_callback(array($this, 'add_limiter_comments'), 'on_every_time');
	    }
	    
	    /**
	     * Includes the complementary javascript file
	     */
	    public function include_js()
	    {
			$regexForJs = $this->wordCountRegex;
			$regexForJs = str_replace("/", "", $regexForJs);
			$regexForJs = str_replace("\\", "\\\\", $regexForJs);
	        echo '<script>var discoWordLimiterRegex = "' . $regexForJs . '";</script>';

	        echo '<script src="'. REASON_PACKAGE_HTTP_BASE_PATH . 'disco/plugins/word_limiter/word_limiter.js' .'"></script>';
	    }
	    
	    
	    /**
	     * Sets a word limit on the given field. 
	     * @param string $field_name -- the field/element to limit
	     * @param int $word_limit -- the number of words to limit to
	     */
	    public function limit_field($field_name, $word_limit)
	    {
	        $this->field_limits[$field_name] = $word_limit;
	    }
	    
	    /**
	     * Get / set whether or not the input limit text for a field should auto show/hide.
	     *
	     * @param string $field_name the field/element to suggest a limit for.
	     * @param boolean $showHideVal show or not
	     */
	    public function auto_show_hide($field_name, $showHideVal = NULL)
	    {
	    	if ($showHideVal !== NULL)
	    	{
	    		$this->auto_show_hide[$field_name] = $showHideVal;
	    	}
	    	return (isset($this->auto_show_hide[$field_name])) ? $this->auto_show_hide[$field_name] : true;
	    }

		private function count_words($val)
		{
			preg_match_all($this->wordCountRegex, $val, $matches);
			return count($matches[0]);
		}
	    
	    /**
	     * Adds comment to each element whose wordcount is being limited. 
	     * These comments will be wrapped in a <span> for easy access by js
	     *
	     */
	    public function add_limiter_comments()
	    {
	        foreach($this->field_limits as $field => $word_limit)
	        {
	            // grab value from DB, count its words, calculate how many remain
	            $element_value = $this->disco_form->get_value($field);
	            $words_remaining = $word_limit - $this->count_words($element_value);
	            
	            $auto_show_hide = ($this->auto_show_hide($field)) ? ' autoShowHide' : '';
	            $formatted_word_limit = '<div class="smallText showRemaining wordInputLimitNote' . $auto_show_hide . '" style="display: none;">
	                Words remaining: <span class="wordsRemaining">'. $words_remaining . '</span>
                    <span class="wordLimit", style="display: none;">'. $word_limit . '</span></div>';
                
                $over_limit_note = '<div class="smallText overWordLimitNote' . $auto_show_hide . '" style="display: none; font-weight:bold;">
                <span class="numWordsOver">0</span> word(s) over the limit! Please shorten text.</b></div>';
                
                $this->disco_form->add_comments($field, $formatted_word_limit);
                $this->disco_form->add_comments($field, $over_limit_note);
	        }
	    }
	    
	    /**
	     * Checks the wordcounts of each field whose words were supposed to be limited
	     * Sets a disco error if the maximum number of words was exceeded
	     */
	    
	    public function check_field_wordcounts()
	    {
	        foreach($this->field_limits as $field => $word_limit)
	        {
	            $num_words = $this->count_words($this->disco_form->get_element_property($field, 'value'));
	            $words_over_limit = $num_words - $word_limit;
	            if($words_over_limit > 0)
	            {
	                $field_display_name = $this->disco_form->get_display_name($field);
	                $this->disco_form->set_error($field, 
	                'There are '. $words_over_limit .' more word(s) than allowed in "'. $field_display_name . 
	                '". Please shorten the text in this field.');
	            }
	        }
	    }
	    
	}
?>

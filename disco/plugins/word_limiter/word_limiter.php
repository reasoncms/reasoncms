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
	     * An array containing field => array containins min/max word limits
	     * @var array
	     */
	    private $field_limits = array();

	    /**
	     * An array containing field => suggested word limits
	     * @var array
	     */
	    // private $suggested_field_limits = array();

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
	     * Sets a word limits on the given field. 
	     * @param string $field_name -- the field/element to limit
	     * @param int $max_limit -- the number of words to limit to
	     * @param int $min_limit -- the number of words to require
	     */
	    public function limit_field($field_name, $max_limit, $min_limit = -1)
	    {
	        $this->field_limits[$field_name]["max"] = $max_limit;
	        $this->field_limits[$field_name]["min"] = $min_limit;
	    }

		private function count_words($val)
		{
			$val = strip_tags($val);

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
	        foreach($this->field_limits as $field => $minMax)
	        {
				$minLimit = $minMax["min"];
				$maxLimit = $minMax["max"];

	            // grab value from DB, count its words, calculate how many remain
	            $element_value = $this->disco_form->get_value($field);
	            $num_words = $this->count_words($element_value);
	            $words_remaining = $maxLimit - $num_words;
	            
	            $formatted_word_limit = '<div class="smallText wordInputLimitNote">' .
					'<span class="wordsEntered">' . $num_words . '</span> word(s) entered.' .
					' (';
					if ($minLimit > 0) {
						$formatted_word_limit .= '<span class="tooFew">' . $minLimit . ' min</span> / ';
					}
					$formatted_word_limit .= '<span class="tooMany">' . $maxLimit . ' max</span>)' .
					'<span class="minWordLimit" style="display: none;">'. $minLimit . '</span>' .
					'<span class="maxWordLimit" style="display: none;">'. $maxLimit . '</span>' .
					'</div>';

                $this->disco_form->add_comments($field, $formatted_word_limit);
				/*
	            $formatted_word_limit = '<div class="smallText showRemaining wordInputLimitNote' . $auto_show_hide . '" style="display: none;">
	                Words remaining: <span class="wordsRemaining">'. $words_remaining . '</span>
                    <span class="maxWordLimit", style="display: none;">'. $maxLimit . '</span></div>';
                
                $over_limit_note = '<div class="smallText overWordLimitNote' . $auto_show_hide . '" style="display: none; font-weight:bold;">
                <span class="numWordsOver">0</span> word(s) over the limit! Please shorten text.</b></div>';

                $this->disco_form->add_comments($field, $formatted_word_limit);
                $this->disco_form->add_comments($field, $over_limit_note);
				 */
	        }
	    }
	    
	    /**
	     * Checks the wordcounts of each field whose words were supposed to be limited
	     * Sets a disco error if the maximum number of words was exceeded
	     */
	    
	    public function check_field_wordcounts()
	    {
	        foreach($this->field_limits as $field => $minMax)
	        {
				$minLimit = $minMax["min"];
				$maxLimit = $minMax["max"];

	            $num_words = $this->count_words($this->disco_form->get_element_property($field, 'value'));
	            $words_over_limit = $num_words - $maxLimit;

				$field_display_name = $this->disco_form->get_display_name($field);
				$errorMessage = "";
	            if($words_over_limit > 0)
	            {
	                $errorMessage = 'There are '. $words_over_limit .' more word(s) than allowed in "'. $field_display_name .  '". Please shorten the text in this field.';
				} else if ($this->disco_form->is_required($field) && $num_words < $minLimit) {
	                $errorMessage = $field_display_name . ' requires at least ' . $minLimit . ' word(s) and there are only ' . $num_words . ' entered.';
				}

				if ($errorMessage != "") {
	                $this->disco_form->set_error($field, $errorMessage);
				}
	        }
	    }
	    
	}
?>

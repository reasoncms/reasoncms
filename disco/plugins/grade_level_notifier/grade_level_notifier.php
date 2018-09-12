<?php
/**
 * @package disco
 * @author Nathaniel MacArthur-Warner
 */
	
	/**
	 * Include dependencies
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
	 * Define constants
	 */
	if(!defined('GRADE_LEVEL_NOTIFIER_MAX_GRADE_LEVEL'))
	{
		define('GRADE_LEVEL_NOTIFIER_MAX_GRADE_LEVEL', 24);
	}
	
	/**
	 * Class to support notification of grade level in disco forms
	 *
	 * Example usage:
	 *
	 * $readlevelnotif = new DiscoGradeLevelNotifier($disco);
	 *
	 * $readlevelnotif->add_field('content');
	 *
	 * @todo add support for grade level thresholds (color coding, messages)
	 * @todo add support for different readability indexes
	 * @todo add support for requiring certain reading levels (e.g. error if reading level is above or below certain point)
	 */
	class DiscoGradeLevelNotifier
	{
		/**
		 * The disco form containing the fields to notify the grade level of
		 * @var object
		 */
		protected $disco_form;
		
		/**
		 * An array containing field => suggested grade level
		 * @var array
		 */
		protected $fields = array();
		
		/**
		 * Disco form must be passed in -- callbacks will be attached to various process points in disco
		 * @param disco_form The Disco form containing fields whose input should be checked and have the author informed of its grade level
		 * @param disco-object $disco_form
		 */
		public function __construct( $disco_form )
		{
			$this->disco_form = $disco_form;
			$this->disco_form->add_callback( array( $this, 'include_js' ), 'pre_show_form' );
			$this->disco_form->add_callback( array( $this, 'add_grade_level_comment' ), 'on_every_time' );
		}
		
		/**
		 * Output the necessary javascript
		 * @return void
		 */
		public function include_js()
		{
			echo '<script src="' . REASON_PACKAGE_HTTP_BASE_PATH . 'disco/plugins/grade_level_notifier/grade_level_notifier.js' . '"></script>';
		}
		
		/**
		 * Apply the reading level report to a given field
		 * @param string $field_name
		 * @return void
		 */
		public function add_field( $field_name )
		{
			array_push( $this->fields, $field_name );
		}
		
		/**
		 * Add comments to the fields to display grade levels
		 *
		 * Used in a callback
		 *
		 * @return void
		 */
		public function add_grade_level_comment()
		{
			foreach( $this->fields as $field)
			{
				$element_value = $this->disco_form->get_value( $field );
				
				$grade_level = self::get_grade_level($element_value);
								
				$formatted_grade_level_notification = '<div class="smallText gradeLevelNotification">';
				
				
				$label = 'Grade Level';
				
				if(defined('REASON_GRADE_LEVEL_LABEL') && REASON_GRADE_LEVEL_LABEL)
				{
					$label = REASON_GRADE_LEVEL_LABEL;
				}
				
				$formatted_grade_level_notification .= '<span class="gradeLevelLabel">' . $label . '</span>: <span class="currentGradeLevel">' . $grade_level . '</span>';
				
				$formatted_grade_level_notification .= '</div>';
				
				$this->disco_form->add_comments($field, $formatted_grade_level_notification);
			}
		}
		
		/**
		 * Convert UTF-8 HTML to a string for grade level analysis
		 *
		 * Removes tags, converts html entities to UTF-8 characters, and strips out whitepace
		 * as preparation for runing through the TextStatistics library.
		 *
		 * @param string $html (UTF-8 encoded)
		 * @return string plain text (UTF-8 encoded)
		 */
		public static function html_to_string($html)
		{
			// strip tags so they don't become part of the calculation
			$text = strip_tags( $html );
			
			// decode html entities so they become normal characters
			$text = html_entity_decode( $text, ENT_HTML5, 'UTF-8' );
			
			// Replace multiple whitepace chars with single spaces again
			mb_internal_encoding('UTF-8');
			$text = preg_replace('/\p{Z}/u', ' ', $text);
	
			// trim
			$text = trim($text);
			
			return $text;
		}
		
		/**
		 * Get the reading grade level of a given HTML string
		 *
		 * @param string $html
		 * @return float Grade level
		 */
		public static function get_grade_level($html)
		{
			$textStatistics = new DaveChild\TextStatistics\TextStatistics;
			if(method_exists($textStatistics, 'setMaxGradeLevel'))
			{
				$textStatistics->setMaxGradeLevel(GRADE_LEVEL_NOTIFIER_MAX_GRADE_LEVEL);
			}
			return $textStatistics->fleschKincaidGradeLevel( self::html_to_string($html) );
		}
	}


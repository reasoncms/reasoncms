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
	 * Class to support notification of grade level in disco forms
	 *
	 * Example usage:
	 *
	 * $readeasenotif = new DiscoReadingEaseNotifier($disco);
	 *
	 * $readeasenotif->add_field('content');
	 *
	 * @todo add support for grade level thresholds (color coding, messages)
	 * @todo add support for requiring certain reading levels (e.g. error if reading level is above or below certain point)
	 */
	class DiscoReadingEaseNotifier
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
			$this->disco_form->add_callback( array( $this, 'add_reading_ease_comment' ), 'on_every_time' );
		}
		
		/**
		 * Output the necessary javascript
		 * @return void
		 */
		public function include_js()
		{
			echo '<script src="' . REASON_PACKAGE_HTTP_BASE_PATH . 'disco/plugins/reading_ease_notifier/reading_ease_notifier.js' . '"></script>';
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
		public function add_reading_ease_comment()
		{
			foreach( $this->fields as $field)
			{
				$element_value = $this->disco_form->get_value( $field );
				
				$score = self::get_reading_ease($element_value);
								
				$formatted_reading_ease_notification = '<div class="smallText readingEaseNotification">';
				
				$label = 'Reading Ease';
				
				if(defined('REASON_READING_EASE_LABEL') && REASON_READING_EASE_LABEL)
				{
					$label = REASON_READING_EASE_LABEL;
				}
				
				$formatted_reading_ease_notification .= '<span class="readingEaseLabel">'.REASON_READING_EASE_LABEL.'</span>: <span class="currentReadingEase">' . $score . '</span>';
				
				$formatted_reading_ease_notification .= ' (<span class="currentReadingEaseLabel">' . self::get_ease_label($score) . '</span>)';
				
				$formatted_reading_ease_notification .= '</div>';
					
					
				
				$this->disco_form->add_comments($field, $formatted_reading_ease_notification);
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
		 * Get the reading ease score of a given HTML string
		 *
		 * @param string $html
		 * @return float Reading ease score
		 */
		public static function get_reading_ease($html)
		{
			$string = self::html_to_string($html);
			$textStatistics = new DaveChild\TextStatistics\TextStatistics;
			return $textStatistics->fleschKincaidReadingEase( $string );
		}
		public static function is_scoreable($string)
		{
			if(mb_strlen($string) < 88)
			{
				return false;
			}
			return true;
		}
		public static function get_ease_label($score)
		{
			$labels = self::get_ease_labels();
			foreach($labels as $min_score => $label)
			{
				if($score >= $min_score)
				{
					return $label;
				}
			}
			return $labels[0];
		}
		public static function get_ease_labels()
		{
			return array(
				80 => 'Very easy to read. Fantastic job!',
				70 => 'Easy to read. Good job!',
				60 => 'Moderately easy to read. Nice!',
				50 => 'OK – not especially easy or hard',
				40 => 'A little hard to read. A few changes might get it above 50.',
				30 => 'Hard to read.  See if you can get it to score above 40.',
				0 => 'Very hard to read. Try to revise to score above 30.',
			);
		}
		public static function get_not_scoreable_label()
		{
			return 'Too short to score';
		}
	}


<?php
/**
 * @package disco
 * @author Nathaniel MacArthur-Warner
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
	 */
	
	class DiscoGradeLevelNotifier
	{
		/**
		 * The disco form containing the fields to notify the grade level of
		 * @var object
		 */
		private $disco_form;
		
		/**
		 * An array containing field => suggested grade level
		 * @var array
		 */
		private $fields = array();
		
		/**
		 * Disco form must be passed in -- callbacks will be attached to various process points in disco
		 * @param disco_form The Disco form containing fields whose input should be checked and have the author informed of its grade level
		 */
		public function __construct( $disco_form )
		{
			$this->disco_form = $disco_form;
			$this->disco_form->add_callback( array( $this, 'include_js' ), 'pre_show_form' );
			$this->disco_form->add_callback( array( $this, 'add_grade_level_comment' ), 'on_every_time' );
		}
		
		public function include_js()
		{
			echo '<script src="' . REASON_PACKAGE_HTTP_BASE_PATH . 'disco/plugins/grade_level_notifier/grade_level_notifier.js' . '"></script>';
		}
		
		public function add_field( $field_name )
		{
			array_push( $this->fields, $field_name );
		}
		
		public function add_grade_level_comment()
		{
			foreach( $this->fields as $field)
			{
				$element_value = $this->disco_form->get_value( $field );
				$textStatistics = new DaveChild\TextStatistics\TextStatistics;
				$grade_level = $textStatistics->fleschKincaidGradeLevel( $element_value );
								
				$formatted_grade_level_notification = '<div class="smallText gradeLevelNotification">' .
					'Current Reading Grade Level: <span class="currentGradeLevel">' . $grade_level . '</span></div>';
				
				$this->disco_form->add_comments($field, $formatted_grade_level_notification);
			}
		}
	}
?>


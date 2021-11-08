<?php
/**
 * @package reason
 * @subpackage content_previewers
 */
/**
 * Register previewer with Reason
 */
$GLOBALS['_content_previewer_class_names'][ basename( __FILE__ ) ] = 'formPreviewer';

/**
 * A previewer for Reason forms
 */
class formPreviewer extends default_previewer {
	protected $gforms_data;
	protected $gforms_download_filename;

	function init( $id, &$page ) {
		parent::init( $id, $page );

		$this->gforms_data              = $this->_entity->get_gravity_forms_json_and_messages();
		$init_label                     = $this->_entity->get_value( 'name' ) ?: $this->_entity->id();
		$this->gforms_download_filename = 'GravityForms-' . preg_replace( '/( |-)/', '_', $init_label ) . '.json';
		if ( isset( $_GET['download_gforms_json'] ) && $_GET['download_gforms_json'] ) {
			$json = $this->gforms_data['json'];

			header( 'Pragma: public' );
			header( 'Expires: 0' );
			header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
			header( 'Cache-Control: private', false );
			header( 'Content-Type: application/json' );
			header( 'Content-Disposition: attachment; filename="' . $this->gforms_download_filename . '";' );
			header( 'Content-Transfer-Encoding: binary' );

			exit( $json );
		}
	}

	function display_entity() {
		$this->show_all_values( $this->_entity->get_values() );

		$data     = $this->gforms_data;
		$link = carl_construct_link(['download_gforms_json' => 'true'], ['site_id', 'type_id', 'id', 'cur_module']);
		$this->show_item_default( 'gravity_forms_json', '<textarea rows="5">' . htmlspecialchars( $data['json'] ) . '</textarea><br><a href="' . $link . '" download>Download Gravity Forms JSON</a> (be sure to note any messages below before importing into WordPress)' );
		if ( ! empty( $data['messages'] ) ) {
			$messages = '<ul><li>' . implode( '</li><li>', $data['messages'] ) . '</li></ul>';
		} else {
			$messages = 'No messages. Import should be clean!';
		}
		$this->show_item_default( 'gravity_forms_export_messages', $messages );
	}

	function show_item_thor_content( $field, $value ) {
		$this->show_item_default( $field, htmlspecialchars( $value ) );
	}

}

?>

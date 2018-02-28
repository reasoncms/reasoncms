<?php
/**
 * Simple reading ease determining script that responds to AJAX POST
 *
 * This is used to maintain consistency in reading ease determination between the browser
 * and in PHP.
 *
 * @package disco
 * @subpackage plugins
 * @author Matt Ryan
 */

include_once( 'paths.php' );
include_once( DISCO_INC . 'plugins/reading_ease_notifier/reading_ease_notifier.php' );

header("Content-Type: application/json");

$json = array(
	'score' => 0,
	'label' => '',
);

if ( isset( $_REQUEST['text'] ) )
{
	if( DiscoReadingEaseNotifier::is_scoreable($_REQUEST['text']))
	{
		$json['score'] = DiscoReadingEaseNotifier::get_reading_ease($_REQUEST['text']);
		$json['label'] = DiscoReadingEaseNotifier::get_ease_label($json['score']);
	}
	else
	{
		$json['score'] = '';
		$json['label'] = DiscoReadingEaseNotifier::get_not_scoreable_label();
	}
}

echo json_encode($json);
<?php
class DorianJHCampsFourFormisthisneeded extends FormStep
{
	var $elements = array(
		'this_is_hidden' => 'hidden',
	);
	function where_to()
	{
		$refnum = $this->controller->get( 'result_refnum' );
		$text = $this->controller->get( 'confirmation_text' );
		reason_include_once( 'minisite_templates/modules/dorian_jh_camps/dorian_jh_camp_confirmation.php' );
		$camp_confirmation = new DorianJHCampConfirmation;
		$hash = $camp_confirmation->make_hash( $text );
		$url = get_current_url();
		$parts = parse_url( $url );
		$url = $parts['scheme'].'://'.$parts['host'].$parts['path'].'?r='.$refnum.'&h='.$hash.'overhere';
		return $url;
	}
}
?>

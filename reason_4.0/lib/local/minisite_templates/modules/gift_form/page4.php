<?php
reason_include_once( 'classes/repeat_transaction_helper.php' );

class GiftPageFourForm extends FormStep
{
	var $elements = array(
		'this_is_hidden' => 'hidden',
	);
	function where_to()
	{
		$refnum = $this->controller->get( 'result_refnum' );
		$text = $this->controller->get( 'confirmation_text' );
		reason_include_once( 'minisite_templates/modules/gift_form/gift_confirmation.php' );
		$gc = new GiftConfirmation;
		$hash = $gc->make_hash( $text );
		$url = get_current_url();
		$parts = parse_url( $url );
		$url = $parts['scheme'].'://'.$parts['host'].$parts['path'].'?r='.$refnum.'&h='.$hash;
		return $url;
	}
}
?>

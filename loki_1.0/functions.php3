<?php

function loki($fieldName,$fieldValue="<p></p>",$bitwise='default',$site_id=-1) {

	$loki = new Loki($fieldName, $fieldValue, $bitwise, $site_id);
	$loki->print_form_children();
}

?>
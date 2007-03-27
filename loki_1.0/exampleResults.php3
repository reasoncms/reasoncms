<?php

include_once('object.php');

$loki = new Loki_Process($_REQUEST['the_field_id']);
$loki2 = new Loki_Process($_REQUEST['the_field_id_2']);

echo $loki->get_field_value();
echo $loki2->get_field_value();

?>
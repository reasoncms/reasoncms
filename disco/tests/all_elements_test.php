<?php
/**
 * @package disco
 */
?>
<html>
<head>
<title>Test all Plasmature elements</title>
<style>
.formElement {
	padding:1em;
}
</style>
</head>
<body>
<?php

$before_classes = get_declared_classes();

include_once(DISCO_INC.'disco.php');
$d = new disco();
$d->set_box_class('stackedBox');

$after_classes = get_declared_classes();

$added = array_diff($after_classes, $before_classes);

$plasmature_types = array();
$exclude = array(
	'defaultType',
	'defaultTextType',
	'tablelinkerType',
	'AssetUploadType',
	'thorType',
	'optionType',
	'option_no_sortType',
);
foreach($added as $classname)
{	
	if(!in_array($classname, $exclude) && ( $pos = strrpos($classname, 'Type') ) )
	{
		if($pos = ( strlen($classname) - 4 ) )
		{
			if(is_a($classname, 'defaultType', true))
			{
				$plasmature_types[] = substr($classname, 0, $pos);
			}
		}
	}
}
foreach($plasmature_types as $type)
{
	$options = array('' => '--','0' => 'Zero', '1' => 'One');
	$d->add_element($type, $type);
	$d->set_value($type, 1);
	
	$el = $d->get_element($type);
	if(is_a($el, 'optionType'))
	{
		$d->set_element_properties($type, array('options' => $options) );
	}
	elseif(is_a($el, 'checkboxType'))
	{
		$d->set_element_properties($type, array('checked_value' => 'checked' ));
	}
}

$d->run();

?>
</body>

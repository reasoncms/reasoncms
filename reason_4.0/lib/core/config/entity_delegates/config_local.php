<?php

$entity_delegates_config_setting_local = array(
	/* 'image' => array(
		'append' => array(
			'entity_delegates/image_append_1.php',
			'entity_delegates/image_append_2.php',
		),
		'prepend' => array(
			'entity_delegates/image_prepend_1.php',
			'entity_delegates/image_prepend_2.php',
		),
		'remove' => array(
			'entity_delegates/image.php',
		),
		'replace' => array(
			'entity_delegates/image_replace_original.php' => 'entity_delegates/image_replace_new.php',
		),
	), */
);

$config = get_entity_delegates_config();

$config->process($entity_delegates_config_setting_local);

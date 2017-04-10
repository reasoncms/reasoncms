<?php

reason_include_once('classes/entity_delegates_config.php');

/* $entity_delegates_config_setting = array(
	'image' => array(
		'append' => array(
			'entity_delegates/image.php',
			'entity_delegates/image_replace_original.php',
		),
	),
	'site' => array(
		'append' => array(
			'entity_delegates/site.php',
		),
	),
	'publication_type' => array(
		'append' => array(
			'entity_delegates/publication.php',
		),
	),
); */

$entity_delegates_config_setting = array(
	'image' => array(
		'append' => array(
			'entity_delegates/image.php',
		),
	),
	'asset' => array(
		'append' => array(
			'entity_delegates/asset.php',
		),
	),
	'event_type' => array(
		'append' => array(
			'entity_delegates/event.php',
		),
	),
	'publication_type' => array(
		'append' => array(
			'entity_delegates/publication.php',
		),
	),
	'feature_type' => array(
		'append' => array(
			'entity_delegates/feature.php',
		),
	),
	'field' => array(
		'append' => array(
			'entity_delegates/field.php',
		),
	),
	'social_account_type' => array(
		'append' => array(
			'entity_delegates/social_account.php',
		),
	),
	'av' => array(
		'append' => array(
			'entity_delegates/media_work.php',
		),
	),
);


$config = get_entity_delegates_config();

$config->process($entity_delegates_config_setting);

if(reason_file_exists('config/entity_delegates/config_local.php'))
	reason_include_once('config/entity_delegates/config_local.php');
	
$config->enter_append_only_mode();
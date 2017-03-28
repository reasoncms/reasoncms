<?php

function result_to_html($test_name, $pass)
{
	$style = $pass ? 'color:green;' : 'color:red;';
	$word = $pass ? 'Pass' : 'Fail';
	return '<p>'.$test_name.': <strong style="'.$style .'">'.$word.'</strong></p>';
}

include_once('reason_header.php');
reason_include_once('classes/entity.php');
reason_include_once('classes/entity_selector.php');

echo '<h3>Full site entity selector</h3>';

$es = new entity_selector();
$es->add_type(id_of('site'));
$sites = $es->run_one();

echo result_to_html('Entity selector returns nonzero results', !empty($sites));

$first = reset($sites);

echo result_to_html('First site is fully populated', $first->full_fetch_performed() );

$values = $first->get_values();

echo result_to_html('First site has base_url value', isset($values['base_url']) );

echo '<h3>Table-limited entity selector</h3>';

$es = new entity_selector();
$es->add_type(id_of('site'));
$es->limit_tables();

$sites = $es->run_one();
$first = reset($sites);

echo result_to_html('Entity selector returns nonzero results', !empty($sites));

$first = reset($sites);

echo result_to_html('First site should not be fully populated', !$first->full_fetch_performed() );

$values = $first->get_values();

echo result_to_html('First site should not have fetched base_url value', !isset($values['base_url']) );

$base_url = $first->get_value('base_url');

echo result_to_html('First site should fetch base_url value upon request', ( $base_url !== FALSE ) );

echo result_to_html('First site should now be fully populated', $first->full_fetch_performed() );

echo '<h3>Field-limited entity selector</h3>';

$es = new entity_selector();
$es->add_type(id_of('site'));
$es->limit_fields('site_state');
$sites = $es->run_one();
$first = reset($sites);

echo result_to_html('Entity selector returns nonzero results', !empty($sites));

$first = reset($sites);

echo result_to_html('First site should not be fully populated', !$first->full_fetch_performed() );

$values = $first->get_values();

echo result_to_html('First site should not have fetched base_url value', !isset($values['base_url']) );

$base_url = $first->get_value('base_url');

echo result_to_html('First site should fetch base_url value upon request', ( $base_url !== FALSE ) );

echo result_to_html('First site should now be fully populated', $first->full_fetch_performed() );

echo '<h3>ID-limited entity selector</h3>';

$es = new entity_selector();
$es->add_type(id_of('site'));
$es->add_relation('`entity`.`id` = "'.reason_sql_string_escape(id_of('master_admin')).'"');
$sites = $es->run_one();
$first = reset($sites);

echo result_to_html('Entity selector returns nonzero results', !empty($sites));

echo result_to_html('Entity selector returns only one result', ( count($sites) === 1 ) );

echo result_to_html('First result has expected ID', ( $first->id() == id_of('master_admin') ) );

$ids = $es->get_ids();

echo result_to_html('get_ids() returns nonzero results', !empty($ids));

echo result_to_html('get_ids() returns only one result', ( count($ids) === 1 ) );

echo result_to_html('get_ids() has expected ID', ( reset($ids) == id_of('master_admin') ) );

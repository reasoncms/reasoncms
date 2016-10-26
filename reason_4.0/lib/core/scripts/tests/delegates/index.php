<?php

function result_to_html($test_name, $pass)
{
	$style = $pass ? 'color:green;' : 'color:red;';
	$word = $pass ? 'Pass' : 'Fail';
	return '<p>'.$test_name.': <strong style="'.$style .'">'.$word.'</strong></p>';
}

include_once('reason_header.php');
reason_include_once('classes/entity.php');

$config = get_entity_delegates_config();

echo '<h3>No test delegate (Using Master Admin site as guinea pig)</h3>';

$e = new entity(id_of('master_admin'));

echo result_to_html('$e->is_or_has(\'testDelegate\') should return FALSE', (false === $e->is_or_has('testDelegate')));

echo result_to_html('$e->method_supported(\'get_test_output\') should return FALSE', (false === $e->method_supported('get_test_output')));

echo result_to_html('$e->get_test_output() should return NULL', (NULL === @$e->get_test_output()));

echo result_to_html('$e->get_value(\'test_column\') should return FALSE', (FALSE === @$e->get_value('test_column')));

echo result_to_html('$e->get_display_name() should not return the string "TEST_DISPLAY_NAME"', ('TEST_DISPLAY_NAME' !== @$e->get_display_name()));

echo '<h3>Appending the test delegate to the site type</h3>';

$config->append('site', 'scripts/tests/delegates/test_delegate.php');

echo result_to_html('$e->is_or_has(\'testDelegate\') should return true', (true === $e->is_or_has('testDelegate')));

echo result_to_html('$e->method_supported(\'get_test_output\') should return true', (true === $e->method_supported('get_test_output')));

echo result_to_html('$e->get_test_output() should return the string "TEST_OUTPUT"', ('TEST_OUTPUT' === @$e->get_test_output()));

echo result_to_html('$e->get_value(\'test_column\') should return the string "TEST_VALUE"', ('TEST_VALUE' === @$e->get_value('test_column')));

echo result_to_html('$e->get_display_name() should return the string "TEST_DISPLAY_NAME"', ('TEST_DISPLAY_NAME' === @$e->get_display_name()));
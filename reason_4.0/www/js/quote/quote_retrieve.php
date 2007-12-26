<?php
include_once('reason_header.php');
reason_include_once( 'classes/quote_helper.php' );

$site_id = (!empty($_REQUEST['site_id'])) ? $_REQUEST['site_id'] : '';
$page_id = (!empty($_REQUEST['page_id'])) ? $_REQUEST['page_id'] : '5';

$cleanup_rules = array('site_id' => array('function' => 'turn_into_int'),
					   'page_id' => array('function' => 'turn_into_int'),
					   'viewed_quote_ids' => array('function' => 'populate_viewed_quote_ids'));

$request = carl_clean_vars($_REQUEST, $cleanup_rules);
$used_ids = (isset($request['viewed_quote_ids'])) ? $request['viewed_quote_ids'] : NULL;

$qh = new QuoteHelper($request['site_id'], $request['page_id']);

if (isset($request['viewed_quote_ids']))
{
	$qh->set_unavailable_quote_ids($request['viewed_quote_ids']);
}

$qh->init();
$quote =& $qh->get_random_quote();

if (!empty($quote))
{
	$quote_id = $quote->id();
	$quote_text = $quote->get_value('description');
}

// if we have a quote id and quote text then return the xml chunk
if (!empty($quote_id) && !empty($quote_text))
{
	header('Content-type: text/xml');
	echo '<?xml version="1.0" encoding="UTF-8"?>';
	echo '<quote>';
	echo '<quote_id>'.$quote_id.'</quote_id>';
	echo '<quote_text>'.$quote_text.'</quote_text>';
	echo '</quote>';
}

// function to explode the posted string of viewed quote ids into an array
function populate_viewed_quote_ids($x)
{
	$quote_ids = explode(",",$x);
	if (!empty($quote_ids))
	{
		foreach($quote_ids as $k=>$id)
		{
			$clean_id = turn_into_int($id);
			if (!empty($clean_id))
			{
				$clean_quote_ids[$k] = $clean_id;
			}
		}
	}
	if (!empty($clean_quote_ids)) return $clean_quote_ids;
}
?>
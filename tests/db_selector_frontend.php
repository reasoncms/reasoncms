<?php

include_once('reason_header.php');
reason_include_once('function_libraries/url_utils.php');

header('Content-Type:text/plain;');

connectDB(REASON_DB);

$es = new entity_selector();
$es->add_type(id_of('site'));
$sites = $es->run_one();

foreach($sites as $site)
{
	echo $site->get_value('name')."\n";
	$es = new entity_selector($site->id()); 
	$es->add_type(id_of('minisite_page'));
	$pages = $es->run_one();
	
	foreach($pages as $page)
	{
		if($link = get_minisite_page_link($site->id(), $page->id() ) )
		{
			echo $link."\n".'----'."\n";
			echo carl_util_get_url_contents($link);
		}
	}
	echo "\n";
}
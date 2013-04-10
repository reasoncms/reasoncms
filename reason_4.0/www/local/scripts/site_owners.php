<?php
include_once('reason_header.php');
reason_include_once('function_libraries/relationship_finder.php');
reason_include_once('function_libraries/admin_actions.php');
reason_include_once('classes/admin/modules/entity_info.php');



// this code causes each site to borrow all categories from the Luther Home site
$es = new entity_selector();
$es->add_type(id_of('site'));
// $es->add_relation('entity.id != ' . id_of('home'));
$sites = $es->run_one();

foreach ($sites as $site){
    downloadCSV($site->get_value('name'), $site->get_value('base_url'), $site->get_value('primary_maintainer'));
}

function maybeEncodeCSVField($string) {
    if(strpos($string, ',') !== false || strpos($string, '"') !== false || strpos($string, "\n") !== false) {
        $string = '"' . str_replace('"', '""', $string) . '"';
    }
    return $string;
}

function downloadCSV($name,$base_url,$primary_maintainer){
    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename=file.csv");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo $name.",".$base_url.",".$primary_maintainer."\n";
}
?>
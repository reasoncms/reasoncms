<?php

include_once('reason_header.php');
reason_include_once('function_libraries/relationship_finder.php');
reason_include_once('function_libraries/admin_actions.php');


// this code causes each site to borrow all categories from the Luther Home site
$es = new entity_selector();
$es->add_type(id_of('form'));
$forms = $es->run_one();
$count = 0;
foreach ($forms as $form_id ) {
    $es2 = new entity_selector($form_id);
//	$es2->add_type(id_of('category_type'));
//	$result2 = $es2->run_one();
//	$site_categories = array_keys($result2);
//
//	$my_cats = array_diff($cats, $site_categories);
//
//	foreach ($my_cats as $new_cat_id)
//	{
//		create_relationship($site_id, $new_cat_id, $borrows_rel_id);

    echo $count . ':' . $form_id . '<br>';
    echo $form;
    //
    //if (in_array('credit_card_payment.php', $forms)) {
    if (in_array('240611', $forms)) {
        echo $form;
    }
    $count++;
}
echo '_______________________';
print_r($forms);
?>
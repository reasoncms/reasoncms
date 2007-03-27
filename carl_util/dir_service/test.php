<pre>
<?

include_once ('directory.php');

$dir = new directory_service(array('mysql'));
$dir->search_by_filter('(|(&(a=b)(c=*d)(!(j~=k)))(ds_email=*g*)(h>=i))');
//$dir->search_by_attribute('ds_email', array('mheiman@carleton.edu','mryan@acs.carleton.edu'), array('ds_fullname'));

//echo $dir->get_first_value('ds_fullname');
//print_r($dir->get_records());
?>
</pre>

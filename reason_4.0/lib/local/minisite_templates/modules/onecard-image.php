<?
require('onecard.php');

if (isset($_REQUEST['id'])) {
	header('content-Type: image/jpeg');
	$card = new onecard;
	if ($image = $card->get_patron_image($_REQUEST['id'])) {
		echo $image;
	} else {
		readfile("noimage.jpg");
	}
}

?>

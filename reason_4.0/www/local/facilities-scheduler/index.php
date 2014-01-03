<?php
 
require_once 'src/Google_Client.php';
require_once 'src/contrib/Google_Oauth2Service.php';
require_once "src/contrib/Google_CalendarService.php";
session_start();
 
$client = new Google_Client();
$client->setUseObjects(true);
$gcal = new Google_CalendarService($client);
//$oauth2 = new Google_Oauth2Service($client);
 
if (isset($_GET['code'])) {
  $client->authenticate($_GET['code']);
  $_SESSION['token'] = $client->getAccessToken();
  $redirect = 'http://reasondev.luther.edu/reason/facilities-scheduler/assign-permissions.php';
  header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
  return;
}
 
if (isset($_SESSION['token'])) {
 $client->setAccessToken($_SESSION['token']);
}
 
if (isset($_REQUEST['logout'])) {
  unset($_SESSION['token']);
  $client->revokeToken();
}
 
if ($client->getAccessToken()) {
    //$user = $oauth2->userinfo->get();
    //$name = filter_var($user['name'], FILTER_SANITIZE_EMAIL);
    //$personMarkup = "$name";
    $_SESSION['token'] = $client->getAccessToken();
} 
else {
    $authUrl = $client->createAuthUrl();
}
?> 
 
 
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Google Calendar Bulk ACLs">
    <meta name="author" content="Luther College">

    <title>Calendar</title>

    <!-- Bootstrap core CSS -->
    <link href="bootstrap/css/bootstrap.css" rel="stylesheet">
    <link href="facilities-scheduler.css" rel="stylesheet">
  </head>

  <body>

    <div class="navbar navbar-inverse navbar-fixed-top">
      <div class="container">
        <div class="navbar-collapse collapse">
          <ul class="nav navbar-nav">
            <li><a href="assign-permissions.php">Assign Permissions</a></li>
            <li><a href="revoke_permissions.php">Revoke Permissions</a></li>
            <li>
            <?php
             if(isset($authUrl)) {
                echo '<a class="btn btn-primary" href='.$authUrl.'>Sign in with Google</a>';
              } else {
                echo '<a class="logout" href="?logout">Logout</a>';
              }
            ?>
            </li>
          </ul>
        </div><!--/.navbar-collapse -->
      </div>
    </div>


<div class="content">
    <?php
     if(isset($authUrl)) {
        echo 'You are not logged in.';
      } else {
        echo 'Choose an action from above.';
      }
    ?>
      <footer>
        <p>&copy; Luther College 2013</p>
      </footer>
    </div> <!-- /container -->


    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="//www.luther.edu/jquery/jquery_latest.js"></script>
    <script src="bootstrap/js/bootstrap.min.js"></script>
  </body>
</html>
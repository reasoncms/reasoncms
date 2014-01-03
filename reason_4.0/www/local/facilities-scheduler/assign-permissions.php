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
  $redirect = 'http://reasondev.luther.edu/g/gc/index.php';
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
    $calendarList = $gcal->calendarList->listCalendarList();
    $calendarArray = Array();
    while(true) {
      foreach ($calendarList->getItems() as $calendarListEntry) {
        $calId = $calendarListEntry->getId();
        $calType = explode("@", $calId)[1];
        if ($calType === 'resource.calendar.google.com') {
          $roomName = $calendarListEntry->getSummary();
          $calBuilding = explode(" - ", $roomName)[0];
          if (array_key_exists($calBuilding,$calendarArray)) {
            $calendarArray[$calBuilding][$roomName] = $calId;
          }
          else {
            $buildingArray = Array();
            $calendarArray[$calBuilding] = $buildingArray;
            $calendarArray[$calBuilding][$roomName] = $calId;
          }
        }
      }
      $pageToken = $calendarList->getNextPageToken();
      if ($pageToken) {
        $optParams = array('pageToken' => $pageToken);
        $calendarList = $gcal->calendarList->listCalendarList($optParams);
      } else {
        break;
      }
    }
    // foreach($calendarArray as $buildingArray) {
    //   sort($buildingArray);
    // }
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
            <li class="active"><a href="assign-permissions.php">Assign Permissions</a></li>
            <li><a href="revoke_permissions.php">Revoke Permissions</a></li>
            <li><a href="http://help.luther.edu">Report an Issue</a></li>
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
      echo '<p>Not signed in</p>';
    } else {
      if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo '<form role="form" method="post">';
        // show form since this is not a form post request
        foreach ($calendarArray as $key => $buildingArray) {
          echo '<div class="building-group">';
          echo '<h2>'.$key.'</h2>';
          foreach ($buildingArray as $room => $email) {
            echo '<div class="checkbox">';
            echo '<label>';
            echo '<input type="checkbox" name="rooms[]" value="'.$email.'">'.$room;
            echo '</label>';
            echo '</div>';
          }
          echo '</div>';
        }
        echo '<div class="form-group">';
        echo '<input type="email" name="email" class="form-control" id="inputEmail1" placeholder="Enter email of user">';
        echo '</div>';
        echo '<div class="radio">';
        echo '<label>';
        echo '<input type="radio" name="aclRadios" value="none" checked>';
        echo '"none" - Provides no access.<br>';
        echo '<input type="radio" name="aclRadios" value="freeBusyReader">';
        echo '"freeBusyReader" - Provides read access to free/busy information.<br>';
        echo '<input type="radio" name="aclRadios" value="reader">';
        echo '"reader" - Provides read access to the calendar. Private events will appear to users with reader access, but event details will be hidden.<br>';
        echo '<input type="radio" name="aclRadios" value="writer">';
        echo '"writer" - Provides read and write access to the calendar. Private events will appear to users with writer access, and event details will be visible.<br>';
        echo '<input type="radio" name="aclRadios" value="owner">';
        echo '"owner" - Provides ownership of the calendar. This role has all of the permissions of the writer role with the additional ability to see and manipulate ACLs.<br>';
        echo '<button type="submit" class="btn btn-primary">Apply</button>';
        echo '</label>';
        echo '</div>';
        echo '</form>';
      }
      else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // ACLs have been submitted at this point do something with them
        // print_r($_POST['rooms']);
        // echo $_POST['aclRadios'];
        // echo $_POST['email'];
        echo '<ul>';
        foreach ($_POST['rooms'] as $room) {
          $rule = new Google_AclRule();
          $scope = new Google_AclRuleScope();
          $scope->setType("user");
          $scope->setValue($_POST['email']);
          $rule->setScope($scope);
          $rule->setRole($_POST['aclRadios']);
          $createdRule = $gcal->acl->insert($room, $rule);
          echo '<li>'.$room.' added for user: '.$_POST['email'].'</li>';
        }
        echo '</ul>';
      }
    }
?>

      <footer>
        <p>&copy; Luther College 2013</p>
      </footer>
      </div>


    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="//www.luther.edu/jquery/jquery_latest.js"></script>
    <script src="bootstrap/js/bootstrap.min.js"></script>
  </body>
</html>
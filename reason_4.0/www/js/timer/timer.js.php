<?php
/**
 * Reason session login timer
 *
 * A php script produces a javascript script that alerts users when they have been or are about
 * to be logged out so they can log back in again.
 *
 * Things look odd in this script as a result.
 *
 * @date  modified February 12, 2007
 * @author Henry Gross
 * @author Ben Cochran - Almost completely rewritten to include div alert and tighten up bugs
 * @package reason
 * @subpackage js
 */
include ('reason_header.php');
reason_include_once ('function_libraries/reason_session.php');
?>
if (window.attachEvent)
  window.attachEvent('onload', init);
else
  window.addEventListener('load', init, false);

window.onscroll = function() {
  if (!popup_alert)
    popup_position();
}
window.onresize = function() {
  if (!popup_alert)
    popup_position();
}

var timeout; // seconds until time to logout (or at least try it)
/* This comment block hides any php error output from js
<?php
$sess =& get_reason_session();
$popup_alert = 'false';
if (DEFAULT_TO_POPUP_ALERT) $popup_alert = 'true';
if($sess->exists())
{
	if( !$sess->has_started() )
	{
		$sess->start();
	}
	if ($sess->get( '_user_popup_alert_pref' ) == 'yes') $popup_alert = 'true';
	elseif ($sess->get( '_user_popup_alert_pref' ) == 'no') $popup_alert = 'false';
}
?>*/
var popup_alert = <?php echo $popup_alert; ?>; // popup alert to warn of impending logout

var has_killed_session = false;

var check_cookie_time = 30;  //in seconds
var timer_time = <?php echo REASON_SESSION_TIMEOUT ?>;  //in minutes
var warn_logout_time = <?php echo REASON_SESSION_TIMEOUT_WARNING ?>;  //in minutes

//THESE SETTINGS WORK WELL FOR DEBUGGING BUT ARE REALLY ANNOYING OTHERWISE
//var check_cookie_time = 5;  
//var timer_time = 1;
//var warn_logout_time = .5;

var refresh_login_link = "<?php echo WEB_JAVASCRIPT_PATH ?>timer/check_session.php"; //link to attempt to refresh session
var logout_page = "<?php echo REASON_LOGIN_URL ?>?msg_uname=expired_login&popup=true"; //link to logout page
var logout_alert_active = false;
var logout_active = false;
var debug_active = false; // shows information about the timeout and cookies and such


// To exclude a form by class or name, add it to the corresponding array.
var exclude_form_classes = new Array("searchForm");

var exclude_form_names = new Array("portalJumpForm","searchJumpForm","search");

var limit_to_form_classes;

function init() {
  // We only really want this script on pages that have a post form.
  if (!post_forms_exist())
    return;

  reset_cookie_timer(timer_time);
  reset_countdown_timer();

  if (debug_active == true)
  {
	// I'd like to be able to see the timer if I'm in debug mode
	// so I'll make this div that'll be fed the timer's value
    var timer_outter_div = document.createElement('div');
    timer_outter_div.id = 'divTimer';  

    var timer_div = timer_outter_div.appendChild(document.createElement('div'));

    var h = timer_div.appendChild(document.createElement('h3'));
    h.id = "hTimer";

    var body = document.getElementsByTagName('BODY')[0];
    body.appendChild(timer_outter_div);
  }

}

/**
 * Check for post forms, also compare form names and classes
 * against the arrays defined above.
 * @return boolean true if the script needs to be run, false if not
 */
function post_forms_exist() {
  var forms = document.getElementsByTagName('form');
  var returnValue = false;
  var formName;
  var formClass;
  var formMethod;
  
  outer_loop:
  for(var i=0; i< forms.length; i++)
  {
    if (forms[i].getAttributeNode("name"))
      formName = forms[i].getAttributeNode("name").nodeValue;
    if (forms[i].getAttributeNode("method"))
      formMethod = forms[i].getAttributeNode("method").nodeValue;

    formClass = forms[i].className;
    
    if (formClass)
    {
      for(var j=0; j < exclude_form_classes.length; j++)
      {
        if (formClass == exclude_form_classes[j])
        continue outer_loop;
      }
    }
    if (formName)
    {
      for(var j=0; j < exclude_form_names.length; j++)
      {
        if (formName == exclude_form_names[j])
        continue outer_loop;
      }
    }
    if (limit_to_form_classes)
    {
      if (formClass)
      {
        for(var j=0;j < limit_to_form_classes.length; j++)
        {
          if (formClass == limit_to_form_classes[j])
          {
            returnValue = true;
            break outer_loop;
          }
        }
      }
    }
    else
    {
      if (formMethod)
      {
        // just in case there's method="POST"
        if (formMethod.toLowerCase() == "post")
        {
          returnValue = true;
          break;
        }
      }
    }
  }
  return returnValue;
}

/**
 * create or reset the cookie used to keep track track of authentication timeout
 * this time is stored in milliseconds given by Date.getTime()
 * @param minutes the length of time in minutes until the authentication timeout
 */
function reset_cookie_timer(minutes) {
  var logout_time = new Date();
  logout_time = logout_time.getTime()+(minutes*60*1000);
  var expires = new Date();
  expires.setTime(logout_time+1000);
  expires = expires.toGMTString();
  document.cookie = 'reason_timeout_countdown='+logout_time+
    '; expires='+expires+'; path=/';
}

/**
 * gets the logout time from the cookie
 * @return int the time the authentication will expire formated by Date.getTime()
 */
function get_logout_time() {
  try {
    // a little messy since I need to get better with regexps
    var cookies = document.cookie.split(';');
    var re = /reason_timeout_countdown=.*/;
    for (var i=0; i< cookies.length; i++)
      if (cookies[i].match(re))
        var cookie = cookies[i].match(re);
    var time = cookie[0].split('=')[1];
  }
  catch(e) {
    var time = 0;
  }
  return time;
}

/**
 * checks whether the alert_active cookie is set to true or false
 * @return boolean true false whether a login alert is active
 */
function check_for_alert() {
  var cookies = document.cookie.split(';');
  var re = /alert_active=.*/;
  for (var i=0; i< cookies.length; i++)
    if (cookies[i].match(re)) {
      var cookie = cookies[i].match(re);
  	  var the_value = cookie[0].split('=')[1];
  	  if (the_value == 'true') 
  	  {
  	  	return true;
  	  }
  	}
  return false;
}

/**
 * resets the countdown timer according to the cookie
 */
function reset_countdown_timer() {
  var maxtime = get_logout_time();
  var currtime = new Date();
  timeout = parseInt((maxtime - currtime.getTime()) / 1000);
  if (logout_alert_active) {
    if (timeout > warn_logout_time*60) {
      logout_alert_active = false;
    }
  }
  countdown();
}

/**
 * counts down the actual timer,
 * periodically synching it with the cookie
 * also, if alert_popup is false, kill the session
 * once we're at 0
 */
function countdown() 
{
 timeout--;

 //debug field to view timer
 if ((debug_active == true) && (timeout <= (timer_time*60 - 3)))	 // we'll give it a few second to make sure the div is there
 {
   // show the current timer, has_killed_sessions, and cookie values
   var h = document.getElementById('hTimer');
   h.innerHTML = "timer: " + timeout + " " + has_killed_session + "<br />"+ document.cookie;
 }

 if (timeout % check_cookie_time == 0) {
   setTimeout("reset_countdown_timer()", 1000);
 }
 else setTimeout("countdown()", 1000);

 if (!popup_alert)
 {
   if (timeout % 3 == 0 && has_killed_session)
   {
     if (check_cookie("REASON_SESSION") && check_cookie("REASON_SESSION_EXISTS"))
     {
       remove_popup_div();
       request_relogin();
       has_killed_session = false;
     }
   }
   else
   {
     if (timeout < 0 && timeout >= -3)
     {
       if (!has_killed_session)
       {
         delete_cookie("REASON_SESSION");
         delete_cookie("REASON_SESSION_EXISTS");
         logout();
         has_killed_session = true;
       }
     }
   }
 }
 if (timeout <= (warn_logout_time*60) && !logout_alert_active) {
   var endtime = new Date();
   if (debug_active == true)
   {
     var debug = endtime.getTime();
   }
   endtime = endtime.getTime() + ((timeout+2)*1000);
   if (endtime >= get_logout_time()) {
     start_logout_timeout();
   }
 }

}

/**
 * start another timeout,
 * this time because the user is in danger of being logged out
 */
function start_logout_timeout() {
  if (popup_alert)
  {
    logout_alert_active = true;
    setTimeout("reset_countdown_timer()", (timeout-3)*1000);
    if (!check_for_alert())
    {
      document.cookie = 'alert_active=true; path=/';
      var alert_logout_time = new Date();
      alert_logout_time.setTime(alert_logout_time.getTime()+(warn_logout_time*60*1000));
      alert('You will be logged out in ' + warn_logout_time + ' minutes due to inactivity. Choose "OK" before ' + alert_logout_time.toLocaleTimeString() + ' to stay logged in. If you choose OK after ' + alert_logout_time.toLocaleTimeString() + ', you will be logged out and asked to log in again.');
      setTimeout("set_alert_active_cookie()", 1000);
      if (logout_alert_active) {
        request_relogin();
      }
    }
  }
}


/**
 * set a cookie to make sure that multiple windows don't spawn multiple alerts
 */
function set_alert_active_cookie()
{
	document.cookie = 'alert_active=false; path=/';
}

/**
 * perform the correct functions depending on if the login worked
 * @param status the status of the login attempt
 */
function refresh_login_status(status) {
  if (status == "true") {
    //alert("You are no longer in danger of being logged out");
    reset_cookie_timer(timer_time);
    logout_active = false;
    logout_alert_active = false;
  }
  else if (!logout_active) {
    //alert("I'm sorry, you could not be logged back in");
    logout_active = true;
    set_alert_active_cookie(); // make sure this is true in case the timeout has not yet executed
    check_session_reload();
    logout();
  }
}

/**
 * you botched your saving throw and now you are logged out
 */
function logout() {
  load_popup_div();
  if (popup_alert) request_relogin();

}

/**
 * alert the user that they have been logged out
 * and provide a link for them to log back in
 */
function load_popup_div() {
  var popup_exists = document.getElementById('divPopup');
  if (popup_exists)
    return;
  var popup_outter_div = document.createElement('div');
  popup_outter_div.id = 'divPopup';
  var popup_div = popup_outter_div.appendChild(document.createElement('div'));

  var h = popup_div.appendChild(document.createElement('h3'));
  h.appendChild(document.createTextNode("You have been logged out due to inactivity!"));
  var a = popup_div.appendChild(document.createElement('a'));
  a.appendChild(document.createTextNode("Renew Your Login"));
  a.onclick = login_click;
  a.href = logout_page;
  a.target = '_blank';
  a.style.color = '#000099';

  var popup_overlay = document.createElement('div');
  popup_overlay.id="popupOverlay";

  var body = document.getElementsByTagName('BODY')[0];
  body.appendChild(popup_overlay);
  body.appendChild(popup_outter_div);
  popup_position();
}

/**
 * Time to get rid of the popup div
 */
function remove_popup_div() {
  var popup_to_remove = document.getElementById('divPopup');
  var popup_overlay_to_remove = document.getElementById('popupOverlay');
  if (popup_to_remove)
    popup_to_remove.parentNode.removeChild(popup_to_remove);
  if (popup_overlay_to_remove)
    popup_overlay_to_remove.parentNode.removeChild(popup_overlay_to_remove);
}

/**
 * non-obtrusive javascript to open the login box in a new window
 */
function login_click() {
  remove_popup_div();
  login_window = window.open(logout_page, "login_window", "toolbar=0, menubar=0, status=1, location=0, scrollbars=1, resizable=1, directories=0, height=600, width=800");
  if (login_window) {
    return false;
  }
}

/**
 * we want to be watching expectantly for them to renew their session
 */
function check_session_reload() {
  if (logout_active) {
    setTimeout("check_session_reload()", check_cookie_time*1000);
  }
  request_relogin();
}

/**
 * use XMLHtppRequest to try to refresh the login
 * @return boolean true if the attempt was successful, false otherwise
 */
function request_relogin() {
  var req;
  
  if (window.XMLHttpRequest)
  {
    req = new XMLHttpRequest();
  }
  else
  {
    if (window.ActiveXObject)
    {
      try {
        req = new ActiveXObject("Microsoft.XMLHTTP");
      } catch(e) {
        return false;
      }
    }
    else
      return false;
  }

  req.onreadystatechange = function() {
    if (req.readyState == 4)
    {
      if (req.status == 200)
      {
        // I doesn't really get along with responseXML so we must do this...
        if (document.implementation && document.implementation.createDocument){
          xmlDoc = req.responseXML;
        } else if (window.ActiveXObject){
          // Make a container to put the xml
          var xmlContainer = document.createElement('xml');
          xmlContainer.setAttribute('innerHTML',req.responseText);
          xmlContainer.setAttribute('id','_fromAjax');
          document.body.appendChild(xmlContainer);
          document.getElementById('_fromAjax').innerHTML = req.responseText;
          // Now we can get the XML, just as responseXML should return
          xmlDoc = document.getElementById('_fromAjax');
          // And get rid of the element
          document.body.removeChild(document.getElementById('_fromAjax'));
        }
        else{
          return false;
        }
        var result = xmlDoc.getElementsByTagName('status').item(0).firstChild.data;
        refresh_login_status(result);
      }
    }
  };
  
  
  // Nasty, but IE was giving problems with cached versions.
  myRand = parseInt(Math.random()*99999999);
  req.open("GET", refresh_login_link + "?rand=" + myRand, true);
  req.send(null);

  return true;
}

/**
 * Delete the cookie. (A "." must be added to the domain
 * because that's how php sets it).
 * @param cookie_name the name of the cookie to delete
 */
function delete_cookie(cookie_name)
{
  var cookie_date = new Date();
  cookie_date.setTime(cookie_date.getTime()-1);
  document.cookie = cookie_name += "=; domain=."+window.location.host+"; expires=" + cookie_date.toGMTString() +"; path=/";
}

/**
 * checks to see if a certain cookie exists
 * @param cookie_name the name of the cookie to check for
 */
function check_cookie(cookie_name)
{
  if (document.cookie.length > 0)
  {
    var results = document.cookie.indexOf(cookie_name + "=");
    if (results != -1)
      return true;
  }
  return false;  
}

/**
 * Decides where the popup window should be positioned
 * so that it's always centered
 */
function popup_position() {
  var popupElement = document.getElementById('divPopup');
  if (!popupElement)
    return;
	var pagesize = popup_getPageSize();	
	var arrayPageScroll = popup_getPageScrollTop();
	var popup_width = popupElement.offsetWidth;
	var popup_height = popupElement.offsetHeight;
	popupElement.style.left = (arrayPageScroll[0] + (pagesize[0] - popup_width)/2)+"px";
	popupElement.style.top = (arrayPageScroll[1] + (pagesize[1]-popup_height)/2)+"px";
  var overlayElement = document.getElementById('popupOverlay');
  if (!overlayElement)
    return;
  overlayElement.style.height = popup_height + 25 + "px";  
  overlayElement.style.width = popup_width + 25 + "px";
  overlayElement.style.left = (arrayPageScroll[0] + (pagesize[0] - popup_width-25)/2)+"px";
  overlayElement.style.top = (arrayPageScroll[1] + (pagesize[1]-popup_height-25)/2)+"px";
}

/**
 * A helper function for popup_position()
 */
function popup_getPageScrollTop(){
	var yScrolltop;
	var xScrollleft;
	if (self.pageYOffset || self.pageXOffset) {
		yScrolltop = self.pageYOffset;
		xScrollleft = self.pageXOffset;
	} else if (document.documentElement && document.documentElement.scrollTop || document.documentElement.scrollLeft ){	 // Explorer 6 Strict
		yScrolltop = document.documentElement.scrollTop;
		xScrollleft = document.documentElement.scrollLeft;
	} else if (document.body) {// all other Explorers
		yScrolltop = document.body.scrollTop;
		xScrollleft = document.body.scrollLeft;
	}
	arrayPageScroll = new Array(xScrollleft,yScrolltop) 
	return arrayPageScroll;
}

/**
 * Another helper function for popup_position()
 */
function popup_getPageSize(){
	var de = document.documentElement;
	var w = window.innerWidth || self.innerWidth || (de&&de.clientWidth) || document.body.clientWidth;
	var h = window.innerHeight || self.innerHeight || (de&&de.clientHeight) || document.body.clientHeight
	arrayPageSize = new Array(w,h) 
	return arrayPageSize;
}

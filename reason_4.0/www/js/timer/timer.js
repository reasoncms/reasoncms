/**
 * Reason session login timer v2.0
 *
 * A rewrite of session login timer v1.0 by Henry Gross and Ben Cochran
 *
 * - grabs params via ajax request
 * - fully jquerified
 * - handles multiple tabs / windows
 * - gracefully handles computer going to sleep
 * 
 * Requires companion file timer.php in the same directory as timer.js - timer.php provides settings via JSON.
 *
 * @author Nathan White
 *
 * @package reason
 * @subpackage js
 */

/**
 * changed to window.load instead of document.ready as this was causing jquery errors in IE - maybe because the JSON
 * request was going out too fast??
 */
$(window).load(function()
{	
	/**
	 * enable me if you are working on this and want to see a live view of the important variables
	 */
	var debug_active = false;
	
	/**
	 * script paths grab dynamically from src attribute
	 */
	var js_src = $('script[src*="timer.js"]:first').attr("src");
	var js_php_src = js_src.replace("timer.js", "timer.php");
	
	/**
	 * declare variables we will populate later
	 */
	var reason_session_timeout;
	var reason_session_timeout_warning;
	var popup_alert;
	var logout_page;
	
	/**
	 * set a few defaults
	 */
	var logout_alert_active = false;
	var logout_active = false;
	var exclude_form_classes = new Array("searchForm");	
	var exclude_form_names = new Array("portalJumpForm","searchJumpForm","search");
		
	/**
	 * We only want to run this at all if we have an existing reason session.
	 */
	if (reason_session_exists())
	{
		init();
	}
	
	/**
	 * Start our timer.
	 *
	 * All page hits will extend the reason_timeout_countdown if we have an active session, but we'll only display
	 * warnings and the logout popup on pages where post_forms_exists() returns true.
	 *
	 * - If post_forms_exists() returns true and we have an active session, we have an active timer on the page
	 * - If post_forms_exists() returns false, we just update the timeout countdown cookie (reason_timeout_countdown)
	 */
	function init()
	{
		$.getJSON(js_php_src, function(json)
		{
			reason_session_timeout = json.timeout;
			reason_session_timeout_warning = json.warning;
			logout_page = json.logout_page;
			popup_alert = (json.popup_alert == "true"); // force to boolean
			
			if (post_forms_exist() && json.session_is_active == "true")
			{
				if (debug_active == true) init_debug(); // we only show on pages with post forms
				$(window).scroll(popup_position);
				$(window).resize(popup_position);
				set_logout_time();
				if (check_for_alert()) set_timeout_warning_active();
				countdown_to_logout();
			}
			else if (json.session_is_active)
			{
				set_logout_time();
				if (check_for_alert()) set_timeout_warning_active();
			}
 		});
	}
	
	/**
	 * Initialize a self-updating debug panel
	 */
	function init_debug()
	{
		var timer_html = $('<div id="divTimer"><div><h3 id="hTimer"></h3></div></div>');
	  	$("body").prepend(timer_html);
	  	var request_relogin_html = $('<div id="requestLogin"><a href="#">Extend Session</a></div>');
	  	$(request_relogin_html).click(function()
	  	{
	  		var update_timer = function(session_extended)
	  		{
	  			if (session_extended == 'true')
	  			{
	  				set_logout_time();
	  				if (check_for_alert()) set_timeout_warning_active();
	  			}			
	  		};
	  		extend_session(update_timer);
	  	});
	  	$("#divTimer").prepend(request_relogin_html);
	  	var kill_session_html = $('<div id="killSession"><a href="#">Kill Session</a></div>');
	  	$(kill_session_html).click(force_logout);
	  	$("#divTimer").prepend(kill_session_html);
	  	setInterval(debug_update, 1000);
	}

	/**
	 * Update the values in the debug panel.
	 */
	function debug_update()
	{
		$("#hTimer").each(function()
	 	{
	 		var my_html = "<ul><li>current_time: " + get_current_time() + "</li>";
	 		my_html += "<li>logout_time: " + get_logout_time() + "</li>";
	 		my_html += "<li>warning_time: " + get_warning_time() + "</li>";
	 		my_html += "</ul>";
	 		// if (document.cookie) my_html += "<p>" + document.cookie + "</p>";
	 		$(this).html(my_html);
	 	});
	}
	
	/**
	 * Check for post forms, also compare form names and classes against the arrays defined above.
	 * @todo handle form with multiple classes
	 * @return boolean true if the script needs to be run, false if not
	 */
	function post_forms_exist()
	{
		var post_forms_exist = false;
		$("form").each(function()
		{
			var form_name = $(this).attr('name');
			var form_class = $(this).attr('class');
			var form_method = $(this).attr('method');
			var in_exclude_form_name = (form_name && ($.inArray(form_name, exclude_form_names) > -1));
			var in_exclude_form_class = (form_class && ($.inArray(form_class, exclude_form_classes) > -1));
			if (!in_exclude_form_name && !in_exclude_form_class)
			{
				if (form_method && (form_method.toLowerCase() == "post")) 
				{
					post_forms_exist = true;
				}
			}
		});
		return post_forms_exist;
	}
	
	/**
	 * @return boolean true / false do REASON_SESSION and REASON_SESSION_EXISTS cookies both exist?
	 */
	function reason_session_exists()
	{
		return ($.cookie("REASON_SESSION") && $.cookie("REASON_SESSION_EXISTS"));
	}
	
	/**
	 * Create or reset the logout time stored in reason_timeout_countdown cookie
	 */
	function set_logout_time()
	{
		var expire_time = new Date();
		expire_time.setTime(expire_time.getTime()+(reason_session_timeout*60*1000)+1000);
		$.cookie('reason_timeout_countdown', expire_time.getTime(-1000), { path: '/', expires: expire_time });
	}
	
	/**
	 * Remove the reason_timeout_countdown cookie
	 */
	function clear_logout_time()
	{
		$.cookie('reason_timeout_countdown', null, { path: '/' });
	}
	
	/**
	 * Set reason_timeout_warning cookie to true - signifying the javascript alert is active
	 */
	function set_timeout_warning_active()
	{
		var expire_time = new Date();
		expire_time.setTime(get_logout_time());
		$.cookie('reason_timeout_warning', "true", { path: '/', expires: expire_time });
	}
	
	/**
	 * Remove reason_timeout_warning cookie
	 */
	function remove_timeout_warning_active()
	{
		$.cookie("reason_timeout_warning", null, { path: '/' });
	}
	
	/**
	 * Gets the logout time from the cookie reason_timeout_countdown
	 *
	 * @return int Date.getTime() formatted time to force logout
	 */
	function get_logout_time()
	{
		return $.cookie("reason_timeout_countdown");
	}
	
	/**
	 * Gets the current time
	 *
	 * @return int Date.getTime() formatted current time
	 */
	function get_current_time()
	{
		var current_time = new Date();
		current_time = current_time.getTime();
		return current_time;
	}
	
	/**
	 * Warning time is calculated by subtracting time from the logout time
	 *
	 * @return int Date.getTime() formatted time to fire the warning
	 */
	function get_warning_time()
	{
		var millisecond_diff = ((reason_session_timeout - reason_session_timeout_warning)*60*1000);
		var logout_time = get_logout_time();
		if (logout_time)
		{
			return (logout_time - millisecond_diff);
		}
	}
	
	/**
	 * checks whether the alert_active cookie is set to true or false
	 * @return boolean true false whether a login alert is active
	 */
	function check_for_alert()
	{
		return ($.cookie("reason_timeout_warning") == "true");
	}
	
	/**
	 * Delete REASON_SESSION and REASON_SESSION_EXISTS cookies.
	 */
	function force_logout()
	{
		$.cookie("REASON_SESSION", null, { domain: "."+window.location.host, path: '/' });
		$.cookie("REASON_SESSION_EXISTS", null, { domain: "."+window.location.host, path: '/' });
	}

	/**
	 * Recursive countdown that watches for the session cookie which signifies a new session. If found:
	 *
	 * - dimisses popup
	 * - stops recursion and starts the logout timer (countdown_to_logout)
	 */	
	function watch_for_login()
	{
		var mycountdown = setTimeout(function()
	 	{
	 		watch_for_login();
	 	}, 1000);
	 	
	 	if ($.cookie("REASON_SESSION") && $.cookie("REASON_SESSION_EXISTS"))
		{
			$("#divPopup").remove();
			$("#popupOverlay").remove();
			set_logout_time();
			clearTimeout(mycountdown);
			mycountdown = setTimeout(function()
			{
				countdown_to_logout();
			}, 1000);
		}
	}
	
	/**
	 * Recursive countdown that checks the session timer and the actual session. If the user is logged out or it is time to force logout:
	 *
	 * - deletes session cookies if they exist, and the reason_timeout_countdown cookie (assures other windows / tabs recognize logout)
	 * - loads the popup div with link to login
	 * - stops recursion and starts to watch for login (watch_for_login)
	 *
	 */
	function countdown_to_logout() 
	{
		var mycountdown = setTimeout(function()
	 	{
	 		countdown_to_logout();
	 	}, 1000);
	 	
	 	if (get_current_time() > get_logout_time() || (get_logout_time() == undefined) || !($.cookie("REASON_SESSION_EXISTS"))) // if undefined it already expired
		{
			force_logout();
			load_popup_div();
			clear_logout_time();
			clearTimeout(mycountdown);
			mycountdown = setTimeout(function()
			{
				watch_for_login();
			}, 1000);
		}
		if (popup_alert && $.cookie("REASON_SESSION_EXISTS") && (get_current_time() > get_warning_time()))
		{
			logout_warning();	
		}
	}
	
	/**
	 * If the user has chosen javascript alerts - pop an alert up. When okay is clicked, does the following:
	 *
	 * - attempts to extend session
	 * - if successful, update reason_timeout_coundown
	 * - if failed, remove reason_timeout_countdown, which causes countdown_to_logout to popup warning div
	 *
	 * logout_warning uses a cookie called reason_timeout_warning to make sure the alert is not triggered multiple times
	 * by multiple instances of timer.js
	 */
	function logout_warning()
	{
		if (!check_for_alert())
		{
			var alert_logout_time = new Date();
			alert_logout_time.setTime(get_logout_time());
			var alert_text = 'You will be logged out in ' + reason_session_timeout_warning;
			alert_text += ' minutes due to inactivity. Choose "OK" before ' + alert_logout_time.toLocaleTimeString();
			alert_text += ' to stay logged in. If you choose OK after ' + alert_logout_time.toLocaleTimeString();
			alert_text += ', you will be logged out and asked to log in again.';
			set_timeout_warning_active();
			alert(alert_text);
			var extend_session_handler_func = function(result)
			{
				if (result == 'true')
				{
					set_logout_time();
				}
				else
				{
					clear_logout_time();
				}
				remove_timeout_warning_active();
			};
			extend_session(extend_session_handler_func);
		}
	}

	
	/**
	 * Opens the logout box in a new window
	 */
	function login_click()
	{
		login_window = window.open(logout_page, "login_window", "toolbar=0, menubar=0, status=1, location=0, scrollbars=1, resizable=1, directories=0, height=600, width=800");
		if (login_window)
		{
			return false;
		}
	}
	
	/**
	 * Does an ajax request for timer.php, which will extend the session - executes an optional callback with the session refresh status
	 * @param callback function
	 */
	function extend_session(callback) 
	{
		$.getJSON(js_php_src, { rand: parseInt(Math.random()*99999999) },
		function(json)
		{
			if (callback) callback(json.session_is_active);
		});
	}
	
	/**
	 * Alert the user that they have been logged out and provide a link for them to log back in.
	 */
	function load_popup_div()
	{
		var popup_exists = ($("#divPopup").length);
		if (!popup_exists)
		{
			var popup_html = '<div id="popupOverlay"></div>';
			popup_html += '<div id="divPopup"><div>';
			popup_html += '<h3>You have been logged out!</h3>';
			popup_html += '<a href="'+logout_page+'" target="_blank" style="color: #000099">Renew Your Login</a>';
			popup_html += '</div></div>';
			var popup = $(popup_html);
			$("a", popup).click(login_click);
			$("body").prepend(popup);
	  		popup_position();
	  	}
	}

	/**
	 * Position the login popup div so that it is always centered.
	 */
	function popup_position()
	{
		$("#divPopup:first").each(function()
		{
			var popup = this;
			var top = (( $(window).height() - $(this).outerHeight() ) / 2+$(window).scrollTop());
			var left = (( $(window).width() - $(this).outerWidth() ) / 2+$(window).scrollLeft());
			$(this).css("position","absolute");
			$(this).css("top",top+"px");
			$(this).css("left",left+"px");
			$("#popupOverlay:first").each(function()
    		{
    			$(this).css("position","absolute");
    			$(this).css("top",(top-15)+"px");
    			$(this).css("left",(left-15)+"px");
    			$(this).css("width",($(popup).outerWidth() + 30)+"px");
    			$(this).css("height",($(popup).outerHeight() + 30)+"px");
    		});
    	});
    }
});

/**
 * Cookie plugin
 *
 * Copyright (c) 2006 Klaus Hartl (stilbuero.de)
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 */

/**
 * Create a cookie with the given name and value and other optional parameters.
 *
 * @example $.cookie('the_cookie', 'the_value');
 * @desc Set the value of a cookie.
 * @example $.cookie('the_cookie', 'the_value', { expires: 7, path: '/', domain: 'jquery.com', secure: true });
 * @desc Create a cookie with all available options.
 * @example $.cookie('the_cookie', 'the_value');
 * @desc Create a session cookie.
 * @example $.cookie('the_cookie', null);
 * @desc Delete a cookie by passing null as value. Keep in mind that you have to use the same path and domain
 *       used when the cookie was set.
 *
 * @param String name The name of the cookie.
 * @param String value The value of the cookie.
 * @param Object options An object literal containing key/value pairs to provide optional cookie attributes.
 * @option Number|Date expires Either an integer specifying the expiration date from now on in days or a Date object.
 *                             If a negative value is specified (e.g. a date in the past), the cookie will be deleted.
 *                             If set to null or omitted, the cookie will be a session cookie and will not be retained
 *                             when the the browser exits.
 * @option String path The value of the path atribute of the cookie (default: path of page that created the cookie).
 * @option String domain The value of the domain attribute of the cookie (default: domain of page that created the cookie).
 * @option Boolean secure If true, the secure attribute of the cookie will be set and the cookie transmission will
 *                        require a secure protocol (like HTTPS).
 * @type undefined
 *
 * @name $.cookie
 * @cat Plugins/Cookie
 * @author Klaus Hartl/klaus.hartl@stilbuero.de
 */

/**
 * Get the value of a cookie with the given name.
 *
 * @example $.cookie('the_cookie');
 * @desc Get the value of a cookie.
 *
 * @param String name The name of the cookie.
 * @return The value of the cookie.
 * @type String
 *
 * @name $.cookie
 * @cat Plugins/Cookie
 * @author Klaus Hartl/klaus.hartl@stilbuero.de
 */
jQuery.cookie = function(name, value, options) {
    if (typeof value != 'undefined') { // name and value given, set cookie
        options = options || {};
        if (value === null) {
            value = '';
            options.expires = -1;
        }
        var expires = '';
        if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
            var date;
            if (typeof options.expires == 'number') {
                date = new Date();
                date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
            } else {
                date = options.expires;
            }
            expires = '; expires=' + date.toUTCString(); // use expires attribute, max-age is not supported by IE
        }
        // CAUTION: Needed to parenthesize options.path and options.domain
        // in the following expressions, otherwise they evaluate to undefined
        // in the packed version for some reason...
        var path = options.path ? '; path=' + (options.path) : '';
        var domain = options.domain ? '; domain=' + (options.domain) : '';
        var secure = options.secure ? '; secure' : '';
        document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
    } else { // only name given, get cookie
        var cookieValue = null;
        if (document.cookie && document.cookie != '') {
            var cookies = document.cookie.split(';');
            for (var i = 0; i < cookies.length; i++) {
                var cookie = jQuery.trim(cookies[i]);
                // Does this cookie string begin with the name we want?
                if (cookie.substring(0, name.length + 1) == (name + '=')) {
                    cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                    break;
                }
            }
        }
        return cookieValue;
    }
};

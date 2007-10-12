Util.Browser = {
	IE:     !!(window.attachEvent && !window.opera),
	Opera:  !!window.opera,
	WebKit: (navigator.userAgent.indexOf('AppleWebKit/') > -1),
	Gecko:  (navigator.userAgent.indexOf('Gecko') > -1
		&& navigator.userAgent.indexOf('KHTML') == -1)
};


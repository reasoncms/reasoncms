/**
 * reasonAjax Utility
 *
 * For now, we are just declaring a method get_module_identifier.
 *
 * get_module_identifier takes a jquery object and parses out a module_identifier from the class attribute.
 *
 * @author Nathan White
 */
 
(function($)
{
	$.reasonAjax = {};
	$.reasonAjax.get_module_identifier = function( obj )
	{
		var reason_module_identifier_substring = 'module_identifier-';
		var classes = obj.attr('class').split(" ");
		var module_identifier = '';
		$.each(classes, function(i)
		{
			if (classes[i].substring(0,reason_module_identifier_substring.length) == reason_module_identifier_substring)
			{
				module_identifier = classes[i].substring(reason_module_identifier_substring.length);
				return false;
			}
		});
		return module_identifier;
	}
	
	$.reasonAjax.unserialize = function( str )
	{
		var ret = {},
		seg = str.replace(/^\?/,'').split('&'),
		len = seg.length, i = 0, s;
		for (;i<len;i++)
		{
			if (!seg[i]) { continue; }
			s = seg[i].split('=');
			ret[s[0]] = s[1];
		}
		return ret;
	}
})(jQuery);
/**
 * Declares instance variables.
 *
 * @constructor
 *
 * @class Represents a menuitem. Can be extended or used as it is.
 */
UI.Menuitem = function()
{
	var _label, _listener, _disabled;

	/**
	 * Inits the menuitem. Params:
	 *    label		string (should not contain HTML)
	 *    listener	function
	 *    disabled	(optional) boolean
	 */
	this.init = function(params)
	{
		if ( params == null || params.label == '' || params.listener == null )
			throw(new Error('UI.Menuitem.init: invalid paramaters. (label: <<' + params.label + '>>; listener: <<' + params.listener + '>>)'));

		_label = params.label;
		_listener = params.listener;
		_disabled = params.disabled == null ? false : params.disabled;

		return this;
	};

	/**
	 * Returns an appendable chunk to render the menuitem.
	 */
	this.get_chunk = function(doc)
	{
		if ( _disabled )
		{
			var container = doc.createElement('SPAN');
			Util.Element.add_class(container, 'disabled');
		}
		else
		{
			var container = doc.createElement('A');
			container.href = 'javascript:void(0);';
			Util.Element.add_class(container, 'menuitem');
			Util.Event.add_event_listener(container, 'click', function() { _listener(); });
		}

		//container.appendChild( doc.createTextNode(_label) );
		container.innerHTML = _label.replace(' ', '&nbsp;');

		return container;
	};
};

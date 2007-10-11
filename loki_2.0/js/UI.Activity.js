/**
 * @class Displays an indicator that reassures the user that
 * work of some sort is being done in the background.
 * @author Eric Naeseth
 */
UI.Activity = function(base, document, kind, text) {
	var helper = new Util.Document(document);
	if (base.base_uri) base = base.base_uri;
	
	var kinds = {
		small: function()
		{
			var container = helper.create_element('SPAN', {
				className: 'progress_small'
			}, [helper.create_element('IMG', {src: base + 'images/loading/small.gif'})]);
			
			if (text)
				container.appendChild(document.createTextNode(' ' + text));
			
			return container;
		},
		
		arrows: function()
		{
			var container = helper.create_element('SPAN', {
				className: 'progress_arrows'
			}, [helper.create_element('IMG', {src: base + 'images/loading/arrows.gif'})]);
			
			if (text)
				container.appendChild(document.createTextNode(' ' + text));
			
			return container;
		},
		
		large: function()
		{
			var image = helper.create_element('IMG', {
				src: base + 'images/loading/large.gif'
			});
			var container = helper.create_element('DIV', {
				className: 'progress_large'
			}, [image]);
			
			if (text) {
				container.appendChild(helper.create_element('P', {}, [text]));
			}
			
			return container;
		},
		
		bar: function()
		{
			return helper.create_element('IMG', {
				src: base + 'images/loading/bar.gif'
			});
		},
		
		textual: function()
		{
			return helper.create_element('SPAN', {className: 'progress_text'},
				[text || 'Loading…']);
		}
	}
	
	function invalid_type() {
		throw new Error('"' + kind + '" is not a valid kind of activity indicator.');
	}
	
	this.indicator = (kinds[kind] || invalid_type)();
	
	/**
	 * Convenience method for appending the indicator as a child of a parent container.
	 */
	this.insert = function(container)
	{
		container.appendChild(this.indicator);
	}
	
	/**
	 * Convenience method for replacing the indicator with actual content.
	 */
	this.replace = function(replacement)
	{
		if (!this.indicator.parentNode)
			return;
		
		this.indicator.parentNode.replaceChild(replacement, this.indicator);
	}
	
	/**
	 * Convenience method for removing the indicator.
	 */
	this.remove = function()
	{
		if (!this.indicator.parentNode)
			return;
		
		this.indicator.parentNode.removeChild(this.indicator);
	}
}
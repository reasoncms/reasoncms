/**
 * @class A canned state for a Util.State_Machine for displaying errors.
 * @see UI.Error_Display
 */
UI.Error_State = function(message_container)
{
	var display = new UI.Error_Display(message_container);
	var error = null;
	
	/**
	 * Sets the error message. Note that in order for the message to really be
	 * displayed, the machine must enter this state.
	 *
	 * @param	message	Error message to display (either a string or a
	 * 					DocumentFragment).
	 * @param	retry	If provided, the error message will include a "retry"
	 *					link that, if clicked on by the user, will call the
	 *					function provided here.
	 */
	this.set = function(message, retry)
	{
		error = {message: message, retry: (retry || null)};
	}
	
	this.enter = function()
	{
		if (!error) {
			throw new Error('Entered error state, but there is no error!');
		}

		display.show(error.message, error.retry);
	}
	
	this.exit = function()
	{
		display.clear();
		error = null;
	}
}
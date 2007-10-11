/**
 * @constructor Creates a new state machine.
 * @class A "state machine"; an organized way of tracking discrete software states.
 * @author Eric Naeseth
 */
Util.State_Machine = function(states, starting_state)
{
	this.states = states || {};
	this.state = null;
	
	this.change = function(new_state)
	{
		if (typeof(new_state) == 'string') {
			if (!this.states[new_state])
				throw new Util.State_Machine.Error('Unknown state "' + new_state + '".');
			new_state = this.states[new_state];
		}
		
		var old_state = this.state;
		if (old_state) {
			old_state.exit(new_state);
		}
		
		this.state = new_state;
		new_state.enter(old_state);
	}
	
	var machine = this;
	for (var name in this.states) {
		var s = this.states[name];
		
		s.enter = (function(old_entry) {
			return function(old_state) {
				if (arguments.length == 0)
					return machine.change(this);
				return old_entry.apply(this, arguments);
			}
		})(s.enter);
		
		s.machine = this;
	}
	
	if (starting_state)
		this.change(starting_state);
}

Util.State_Machine.Error = function(message)
{
	Util.OOP.inherits(this, Error, message);
	this.name = 'Util.State_Machine.Error';
}
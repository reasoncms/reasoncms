/**
 * @class A synchronization object, based on Lamport's Bakery algorithm.
 * @see http://decenturl.com/en.wikipedia/lamport
 * @author Eric Naeseth
 * @constructor
 */
Util.Lock = function(name)
{
	var threads = {};
	var next_id = 0;
	var active_thread = null;
	
	function pair_less_than(a, b, c, d)
	{
		return (a < c) || (a == c && b < d);
	}
	
	function next_number()
	{
		var max = 0;
		
		for (var i in threads) {
			if (threads[i] && threads[i].number && threads[i].number > max)
				max = threads[i].number;
		}
		
		return 1 + max;
	}
	
	this.acquire = function()
	{
		var thread = {
			id: ++next_id,
			entering: false
		};
		
		threads[thread.id] = thread;
		
		thread.entering = true;
		thread.number = next_number();
		thread.entering = false;
		
		for (var i in threads) {
			if (!threads[i])
				continue;
				
			var t = threads[i];
			
			// wait until the thread receives its number
			while (t.entering) { /* wait */ }
			
			// wait until all threads with smaller numbers or with the same
			// number but higher priority finish their work with whatever has
			// been locked
			while (t.number &&
				pair_less_than(t.number, i, thread.number, thread.id))
			{
				// wait
			}
		}
		active_thread = thread;
		// the thread is now locked
	}
	
	this.release = function()
	{
		active_thread.number = 0;
	}
}
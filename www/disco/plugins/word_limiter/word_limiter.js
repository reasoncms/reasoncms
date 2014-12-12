/**
 * word_limiter.js 
 * Manages the limiting of words a user can input for a given element in a Disco form
 *
 * unlike input_limiter, which this is modeled on and which counts characters,
 * this is NOT yet written to work with tinymce/loki, but could be modified to do so easily enough.
 *
 * Another big difference is that input_limiter hits the server to count things and so lags. This
 * uses the same regex used on the server and so has snappier response.
 * 
 * @author Tom Feiler
 * @requires jQuery
 */
$(document).ready(function()
{
	// construct a regex using a variable put there by the php word_limiter plugin.
	var wordCountingRegex = new RegExp(discoWordLimiterRegex, 'g');

	function count_words(val) {
		var result = val.match(wordCountingRegex);
		return result == null ? 0 : result.length;
	}


    /* 
        - Displays how many words remain to be used once user gets within threshold (20 now) words of limit (hidden otherwise). 
        - Warns user when over the word limit 
    */
    
    function count_and_update(text_element, cur_text)
    {
		// console.log("count_and_update firing on [" + text_element.attr("name") + "]/[" + cur_text + "]");
        var limit_note = text_element.siblings('div.wordInputLimitNote');
        var over_limit_note = text_element.siblings('div.overWordLimitNote');
        var current_text = cur_text;
        var word_limit = parseInt(limit_note.children('span.wordLimit').html());
        var words_remaining_element = limit_note.children('span.wordsRemaining');
        var auto_show_hide = limit_note.hasClass("autoShowHide");

		var threshold = 20;

		var words_so_far = count_words(current_text);

		var words_remaining = word_limit - words_so_far;
		words_remaining_element.html(words_remaining);
		var over_limit = -words_remaining;
		// display over-limit warning
		if(words_remaining < 0)
		{
			limit_note.hide();
			over_limit_note.children('span.numWordsOver').html(over_limit);
			over_limit_note.show();
		}
		// display caution
		else if( !auto_show_hide || (words_remaining < threshold) )
		{
			limit_note.show();
			over_limit_note.hide();
		}
		// not "close" to limit, so hide everything
		else if(auto_show_hide)
		{
			limit_note.hide();
			over_limit_note.hide();   
		}
    }

	var inputAreas = $('div.wordInputLimitNote').siblings('textarea, :text');
	inputAreas.bind('input propertychange', function() {
        count_and_update($(this), $(this).val());
	});


	// trigger character count upon the first rendering of page
    inputAreas.each(function(){
        count_and_update($(this), $(this).val());
    });
		
});

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

	function strip_tags(val) {
		return val.replace(/<.*?>/g, '');
	}

	function count_words(val) {
		val = strip_tags(val);

		var result = val.match(wordCountingRegex);
		return result == null ? 0 : result.length;
	}

	var originalLimiterFormat = {};

    function count_and_update(text_element, cur_text)
    {
		// console.log("count_and_update firing on [" + text_element.attr("name") + "]/[" + cur_text + "]");
        var limit_note = text_element.siblings('div.wordInputLimitNote');
        // var over_limit_note = text_element.siblings('div.overWordLimitNote');
        var current_text = cur_text;
        var word_limit_min = parseInt(limit_note.children('span.minWordLimit').html());
        var word_limit_max = parseInt(limit_note.children('span.maxWordLimit').html());
        // var words_remaining_element = limit_note.children('span.wordsRemaining');

		var words_so_far = count_words(current_text);

        limit_note.children('span.wordsEntered').html(words_so_far);
		// console.log("entered [" + words_so_far + "] words. limits are [" + word_limit_min + "] -> [" + word_limit_max + "]");

		if (originalLimiterFormat[text_element.attr("name")] == undefined) {
			originalLimiterFormat[text_element.attr("name")] = {
				"fewColor": limit_note.children('span.tooFew').css('color'),
				"manyColor": limit_note.children('span.tooMany').css('color')
			};
		}


		if (word_limit_min != -1) {
			limit_note.children('span.tooFew').css('color', words_so_far < word_limit_min ? 'red' : originalLimiterFormat[text_element.attr("name")].fewColor);
		}
		limit_note.children('span.tooMany').css('color', words_so_far > word_limit_max ? 'red' : originalLimiterFormat[text_element.attr("name")].manyColor);
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

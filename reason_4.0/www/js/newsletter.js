$(document).ready(function()
{
	function templateViewer()
	{
		$(".templateName").hide();
		templateLister = new Array;
		currentTemplate = new String;
		$(".newsletterTemplate").each(function()
		{
			$(this).hide();
		});
		$("#templateChooserRow").hide();
		$("#templateChooserRow").find('input[type="radio"]').each(function()
		{
			radioButtonName = new String;
			radioButtonName = $(this).attr('id');
			templateDisplayName = $('label[for="'+radioButtonName+'"]').html();
			templateLister[$(this).val()] = templateDisplayName;
			if ($(this).attr("checked") == true)
			{
				currentTemplate = $(this).val();
			}
		});
		// make this so that its result is the same as what disco would output, so you don't have to muck about with JS clicking the right radio button. 
		selectorHTML = '<select id="templateSelect">';
		for (method in templateLister) 
		{
			optionHTML = '<option value="' + method + '">' + templateLister[method] + '</option>';
			selectorHTML = selectorHTML + optionHTML;
		}
		selectorHTML = selectorHTML + '</select><br /><br />';
		$("#previewDiv").prepend(selectorHTML);
		if (currentTemplate != '') $('#templateSelect').val(currentTemplate);
		$('#'+$('#templateSelect').val()).show();
		$('#templateChooserRow input[type="radio"][value="'+$('#templateSelect').val()+'"]').attr("checked", true);
		$(document).on("change", "#templateSelect", function()
		{
			$('.newsletterTemplate').hide();
			$('#' + $(this).val()).show();
			$('#templateChooserRow input[type="radio"][value="'+$(this).val()+'"]').attr("checked", true);
		});
	}

	function sendTabs()
	{
		introText = '<ol><li>Open the email client of your choice and create a new message</li><li>Enter the recipients and subject</li><li>Press the "Select Newsletter Text" button below</li><li>Copy the selection by pressing CTRL-C on PC or CMD-C on Mac, from the Edit menu of your Web browser, or by right-clicking on the selected text.</li><li>Return to your email client and paste in the content of the newsletter by pressing CTRL-V on PC or CMD-V on Mac, or from the Edit menu of your email client.</li></ol><p>Note: If the content looks unformatted, you will need to turn on formatting in your email client before pasting.</p><p><a href="#" id="select">Select Newsletter Text</a></p><br />';
		$("p.basicInstructions").after('<div id="copyInstructions">' + introText + '</div>');	
		$("a#select").click(function()
		{
			var doc = document
    			, text = doc.getElementById("html")
    			, range, selection
    		;
    		if (doc.body.createTextRange)
    		{
    			range = document.body.createTextRange();
    			range.moveToElementText(text);
    			range.select();
    		}
    		else if (window.getSelection)
    		{
    			selection = window.getSelection();
    			range = document.createRange();
    			range.selectNodeContents(text);
    			selection.removeAllRanges();
    			selection.addRange(range);
    		}
    		return false;
    	});
	}
	
	function checkAlls()
	{
		$('[id^="pubpostsgroup"] .element>div>table>tbody').each(function() 
		{
			$(this).prepend('<tr><td style="border-bottom: 1px solid black;" valign="top"></td><td style="border-bottom: 1px solid black;" valign="top">Select: <a href="#" class="checkAll">All</a> <a href="#" class="checkNone">None</a></td></tr>');
		});

		$('.events_wrapper').children('table').children('tbody').prepend('<tr><td style="border-bottom: 1px solid black;" valign="top"></td><td style="border-bottom: 1px solid black;" valign="top">Select: <a href="#" class="checkAll">All</a> <a href="#" class="checkNone">None</a></td></tr>');

		$(".checkAll").click(function(event)
		{
			$(this).parents("table:first").find("input[type=checkbox]").prop("checked", true);
			return false;
		});
		
		$(".checkNone").click(function(event)
		{
			$(this).parents("table:first").find("input[type=checkbox]").prop("checked", false);
			return false;
		});
	}
	
	function zebraTables()
	{
		$('#eventsgroupRow table tbody tr').mouseover(function()
		{
			$(this).addClass("hover");
		}).mouseout(function()
		{
			$(this).removeClass("hover");
		});
		$(".events_wrapper").children("table").children("tbody").children("tr:odd").addClass("zebraStripe");
	}
	
	function firstPage()
	{
		///////////////////// ADD JS-ONLY STUFF //////////////////
		$('<input type="checkbox" id="showPosts">').prependTo($("#pubpostsheader1Row>.words>h2")).prop('checked', true);
		$('<input type="checkbox" id="showEvents">').prependTo($("#eventsheader1Row>.words>h2")).prop('checked', true);
	
		$("#pubpostsheader1Row>.words>h2, #eventsheader1Row>.words>h2").css("display", "inline");
		
		$("#showPosts").change(function()
		{
			if ($(this).prop('checked') == true)
			{
				$("#selectedpublicationsRow, #publicationstartdateRow, #publicationenddateRow").fadeIn();
			} 
			else 
			{
				$("#selectedpublicationsRow, #publicationstartdateRow, #publicationenddateRow").fadeOut();
			}
		});
		$("#showEvents").change(function()
		{
			if ($(this).prop('checked') == true)
			{
				$("#eventsstartdateRow, #eventsenddateRow").fadeIn();
			}
			else
			{
				$("#eventsstartdateRow, #eventsenddateRow").fadeOut()
			}
		});
		
		/**
		 * Lets zero out values if the publication or event categories are not selected at all
		 */
		$("#disco_form[action*='_step=SelectIncludes']").submit(function()
		{
			$("#showPosts:first").each(function()
			{
				if ($(this).prop('checked') == false)
				{
					$("#selectedpublicationsRow, #publicationstartdateRow, #publicationenddateRow").find('input[type="checkbox"]').prop("checked", false);
				}
			});
			
			$("#showEvents:first").each(function()
			{
				if ($(this).prop('checked') == false)
				{
					$("#eventsstartdateRow, #eventsenddateRow").find('input[type="text"]').val("");
				}
			});
		});
	}
	function styleFrames()
	{
		frame = $('iframe')[0].contentWindow.document;
		$("head", frame).append('\
<style type="text/css">\
body {\
	font-family: arial, sans-serif !important;\
	//font-size: xx-large;\
}\
h1 {\
	font-family: inherit !important;\
	font-size: xx-large;\
} \
h2 {\
	font-size: x-large%;\
	font-family: inherit !important;\
}\
h3 {\
	font-family: inherit !important;\
	font-size: large;\
}\
h4 {\
	font-family: inherit !important ;\
	//font-size: 80%;\
} </style>');
	}

	/////////////////////////CHECK FOR PAGE//////////////////////////
	if ($(".newsletterTemplate").length != 0)
	{
		templateViewer();
	}
	if ($("#pubpostsheader1Row").length != 0)
	{
		firstPage();
	
	}
	if ($('[name="newsletter_title"]').length != 0)
	{
		checkAlls();
		zebraTables();
	}
	if ($("#ComposeEmailStep").length != 0) 
	{
		sendTabs();
	}
	$(window).load(function()
	{ 
		if ($('#newsletterlokiRow').length != 0)
			styleFrames(); 
	});
});
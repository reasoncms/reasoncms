$(document).ready(function(){
function templateViewer()
{
	$(".templateName").hide();
	templateLister = new Array;
	currentTemplate = new String;
	$(".newsletterTemplate").each(function()
		{
			$(this).hide();
		}
	);
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
		}
	)
	// make this so that its result is the same as what disco would output, so you don't have to muck about with JS clicking the right radio button. 
	selectorHTML = '<select id="templateSelect">';
	for (method in templateLister) 
	{
		optionHTML = '<option value="' + method + '">' + templateLister[method] + '</option>';
		selectorHTML = selectorHTML + optionHTML;
	}
	selectorHTML = selectorHTML + '</select><br /><br />';
	$("#previewDiv").prepend(selectorHTML);
	if (currentTemplate != '')
		$('#templateSelect').val(currentTemplate);
	$('#'+$('#templateSelect').val()).show();
	$('#templateChooserRow input[type="radio"][value="'+$('#templateSelect').val()+'"]').attr("checked", true);
	$("#templateSelect").live('change', function()
		{
			$('.newsletterTemplate').hide();
			$('#' + $(this).val()).show();
			$('#templateChooserRow input[type="radio"][value="'+$(this).val()+'"]').attr("checked", true);
		}
	)
}

function sendTabs()
{
	/* $(".haveJS").removeClass("haveJS");
	currentTab = "#use_reason_tab";
	$('#tabList a[href="'+currentTab+'"]').parent().addClass("current");
	innerStuff = $("#html").html() */
	introText = '<ol><li>Open the email client of your choice and create a new message</li><li>Enter the recipients and subject</li><li>Press the "Select Newsletter Text" button below</li><li>Copy the selection by pressing CTRL-C on PC or CMD-C on Mac, from the Edit menu of your Web browser, or by right-clicking on the selected text.</li><li>Return to your email client and paste in the content of the newsletter by pressing CTRL-V on PC or CMD-V on Mac, or from the Edit menu of your email client.</li></ol><p>Note: If the content looks unformatted, you will need to turn on formatting in your email client before pasting.</p><p><a href="#" id="select">Select Newsletter Text</a></p><br />';
	$("p.basicInstructions").after('<div id="copyInstructions">' + introText + '</div>');
	/* $('#use_client_tab').hide();
	discoForm = $("#disco_form").clone();
	goBackButton = $('input[name="__button_back"]', discoForm).clone();
	$("*", discoForm).remove();
	discoForm.append(goBackButton);
	$("#select").before(discoForm);
	
	
	$("#tabList a").click(function(event)
		{
			tabToSelect = $(this).attr("href");
			if (currentTab != tabToSelect)
			{
				$(this).parent().addClass("current");
				$('#tabList a[href="' + currentTab + '"]').parent().removeClass("current");
				$(currentTab).hide();
				currentTab = tabToSelect;
				$(tabToSelect).show();
			}
			event.preventDefault();
		}
	); */
	$("a#select").click(function(event)
		{
		    var text = document.getElementById("html");
		    if ($.browser.msie) {
		        var range = document.body.createTextRange();
		        range.moveToElementText(text);
		        range.select();
		    } else if ($.browser.mozilla || $.browser.opera) {
		        var selection = window.getSelection();
		        var range = document.createRange();
		        range.selectNodeContents(text);
		        selection.removeAllRanges();
		        selection.addRange(range);
		    } else if ($.browser.safari) {
		        var selection = window.getSelection();
		        selection.setBaseAndExtent(text, 0, text, text.innerText.length);
		    }
			event.preventDefault();
		}
	);
	
}
function checkAlls()
{
	var counter = 0;
	$('[id^="pubpostsgroup"] .element>div>table>tbody').each(function() 
		{
			var counter;
			counter++;
			// Prefer a checkbox that says check all? Use the commented line.			
			// $(this).prepend('<tr><td style="border-bottom: 1px solid black;" valign="top"><input type="checkbox" id="checkAllBox' + counter + '" class="checkAll"></td><td style="border-bottom: 1px solid black;" valign="top"><label for="checkAllBox' + counter + '"><em>(Check all)</em></label></td><tr>');
			$(this).prepend('<tr><td style="border-bottom: 1px solid black;" valign="top"></td><td style="border-bottom: 1px solid black;" valign="top">Select: <a href="#" class="checkAll">All</a> <a href="#" class="checkNone">None</a></td><tr>');
		}
	);
// 	Prefer a checkbox that says check all? Use the commented line.
//	$('#eventsgroupRow .words').children('table').children('tbody').prepend('<tr><td></td><td style="border-bottom: 1px solid black;" valign="top"><input type="checkbox" id="checkAllBoxEvents" class="checkAll"><label for="checkAllBoxEvents"><em>(Check all)</em></label></td><tr>');
	$('.events_wrapper').children('table').children('tbody').prepend('<tr><td style="border-bottom: 1px solid black;" valign="top"></td><td style="border-bottom: 1px solid black;" valign="top">Select: <a href="#" class="checkAll">All</a> <a href="#" class="checkNone">None</a></td><tr>');
	/*		
	Prefer a checkbox that says check all? Use the commented lines below.
	$('.checkAll').live('click', 
		function()
		{
			inputStuff = $(this).parent().parent().parent().find("input");
			inputStuff.attr("checked", $(this).is(':checked'));
		}
	)
	*/
	$('.checkAll').live('click', 
		function(event)
		{
			event.preventDefault();
			inputStuff = $(this).parent().parent().parent().find("input");
			inputStuff.attr("checked", true);
		}
	)
	$('.checkNone').live('click', 
		function(event)
		{
			event.preventDefault();
			inputStuff = $(this).parent().parent().parent().find("input");
			inputStuff.attr("checked", false);
		}
	)

}
function zebraTables()
{
	$('#eventsgroupRow table tbody tr').mouseover(function() {
			$(this).addClass("hover");
		}
	).mouseout(function() {
			$(this).removeClass("hover");
		}
	);
	$(".events_wrapper").children("table").children("tbody").children("tr:odd").addClass("zebraStripe");
}
function firstPage()
{

	///////////////////// ADD JS-ONLY STUFF //////////////////
	$('<input type="checkbox" id="showPosts">').prependTo($("#pubpostsheader1Row>.words>h2")).attr('checked', true);
	$('<input type="checkbox" id="showEvents">').prependTo($("#eventsheader1Row>.words>h2")).attr('checked', true);
	
	$("#pubpostsheader1Row>.words>h2, #eventsheader1Row>.words>h2").css("display", "inline");
	$(".needsJS").show();
	
	$("#checkall").click(
		function(objEvent)
		{
			$("input[type='checkbox']:^checked").attr('checked',true);
		}
	);
	$("#uncheckall").click(
		function(objEvent)
		{
			$(":checkbox:checked").attr('checked',false);
		}
	);
	$("#showPosts").live('change',
		function()
		{
			if ($(this).attr('checked') == true)
			{
				inputs = $("#selectedpublicationsRow, #publicationstartdateRow, #publicationenddateRow");
				inputs.fadeIn().find('input[type="checkbox"]').attr("checked", true);
			} else 
			{
				inputs = $("#selectedpublicationsRow, #publicationstartdateRow, #publicationenddateRow");
				inputs.fadeOut().find('input[type="checkbox"]').attr("checked", false);

			}
		}
	);
	$("#showEvents").live('change',function()
		{
			if ($(this).attr('checked') == true)
			{
				$("#eventsstartdateRow, #eventsenddateRow").fadeIn();
			} else
			{
				inputs = $("#eventsstartdateRow, #eventsenddateRow");
				inputs.fadeOut().find('input[type="text"]').attr("value", "");
			}
		}
	);
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
$(window).load(function() { 
		if ($('#newsletterlokiRow').length != 0)
			styleFrames(); 
	}
)
});

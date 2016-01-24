/**
 * jQuery fanciness for the sites inline editing form on the profile module.
 *
 * This javascript depends upon the sites.php form remaining relatively static - if the composition of the basic
 * disco form changes very much (renamed elements, new elements, changes to element groups), this file will likely
 * need updates as well.
 *
 * @author Nathan White
 */
$(document).ready(function()
{
	setup_element_groups();
	setup_submit_button();
	refresh_add_button();

	/**
	 * Setup our element groups
	 *
	 * - Add refresh_an_item event whenever we change the type of any item
	 * - Hide empty items
	 * - Trigger change event for initial setup on visible items
	 * - Remove comments that are intended for javascript off environments
	 */
	function setup_element_groups()
	{
		$("#mainProfileContent div.sites form#disco_form .formElement").each(function()
		{
			var site = $(this);
			site.find('.stackedElement').eq(1).find('div.formComment').remove();
			var remove_button = $('<div style="float: right;" class="remove">(<a href="">remove</a>)</div>');
			var label = $('<h3 class="siteType"></h3>');
			site.prepend(label);
			site.prepend(remove_button);
			
			$('a', remove_button).click(function()
			{
				remove_an_item(site);
				refresh_add_button();
				return false;
			});	
			
			refresh_an_item(site);
		});
	}
	
	/**
	 * On submit we need to refresh and move to end any item that has a type but no URL.
	 *
	 * - This makes sure our element numbering is correct on any subsequent "error" screens.
	 */
	function setup_submit_button()
	{
		$("#mainProfileContent div.sites form#disco_form").submit(function()
		{
			$("#mainProfileContent div.sites form#disco_form .formElement").filter(':visible').each(function()
			{
				if ($.trim($(this).find(".stackedElement").eq(2).find("input:first").val()) == "")
				{
					remove_an_item($(this));
				}
			});
		});
	}
	
	/**
	 * Destroy any existing add_button. Create a new one if we have hidden tables still.
	 */
	function refresh_add_button()
	{
		$("div#add_button").remove();
		if ($("#mainProfileContent div.sites form#disco_form div.formElement:hidden").length > 0)
		{
			// this is icky in IE7. Lets try something a little different.
			//button = $("#mainProfileContent li.sites form#disco_form .formElement select:first").clone().attr("id", "add_a_site").removeAttr("name");
			
			button_html = $("#mainProfileContent div.sites form#disco_form .formElement select:first").html();
			button = $('<select id="add_a_site">'+button_html+'</select>');
			button.find("option:first").removeAttr("value").text("Add link ...");
			button.val("");
			
			// remove options that are already displayed
			$("#mainProfileContent div.sites form#disco_form div.formElement h3.siteType").each(function()
			{
				var text = $(this).text();
				if ((text != "") && (text != "Other"))
				{
					button.find('option[value="'+text+'"]').remove();
				}
			});
			
			// run add_an_item if a value is selected
			button.change(function()
			{
				add_an_item(button.val());	
			});
			
			// put the pull down button into the DOM
			$("#mainProfileContent div.sites form#disco_form div.formElement:last").after(button);
			$("#add_a_site").wrap('<div id="add_button"></div>');
		}
	}
	
	/**
	 * Find the first hidden item, refresh it with the requested type.
	 */
	function add_an_item(type)
	{
		$("#mainProfileContent div.sites form#disco_form div.formElement").filter(':hidden:first').each(function()
		{
			refresh_an_item($(this), type);
		});
		refresh_add_button();
	}
	
	/**
	 * remove an item
	 */
	function remove_an_item(item)
	{
		refresh_an_item(item, "");
		move_to_end_and_reorder(item);
	}
	
	/**
	 * Hide and show fields as appropriate for the type - including hiding the whole thing if no type is "".
	 */
	function refresh_an_item(item, type)
	{
		// if type was passed in, lets set it, otherwise we retrieve it.
		if (arguments.length == 2)
		{
			item.find("select:first").val(type);
		}
		else var type = item.find("select:first").val();
		
		if (type == "")
		{
			item.find("h3.siteType").text("");
			item.find("input").val("");
			item.hide();
		}
		else
		{
			item.find("h3.siteType").text(type);
			item.find(".stackedElement").eq(0).hide();
			if (type == "Other")
			{
				item.find(".stackedElement").eq(1).show();
				item.find(".stackedElement").eq(2).show();
			}
			else
			{
				item.find(".stackedElement").eq(1).hide();
				item.find(".stackedElement").eq(2).show();
			}
			item.show();	
		}
	}
	
	/**
	 * This renumbers the existing elements so that error messages are in sync with submitted order.
	 */
	function move_to_end_and_reorder(item)
	{
		item.insertAfter("#mainProfileContent div.sites form#disco_form div.formElement:last");
		var num = 0;
		$("#mainProfileContent div.sites form#disco_form div.formElement").each(function()
		{
			var site = $(this);
			var site_elements = site.find(".stackedElement");
			
			// change the id
			site.attr("id", "sites"+num+"Item");
			
			// first the error anchor name
			site.find('.element a').attr("name", "sites_"+num+"_error");
			
			// Type of Site
			var type_of_site = site_elements.eq(0);
			type_of_site.find('a:first').attr("name", "item_"+num+"_error");
			type_of_site.find('select:first').attr("id", "item_"+num+"Element").attr("name", "item_"+num);
			
			// Site Name
			var site_name = site_elements.eq(1);
			site_name.find('a:first').attr("name", "item_"+num+"_name_error");
			site_name.find('input:first').attr("id", "item_"+num+"_nameElement").attr("name", "item_"+num+"_name");
			
			// Site URL 
			var site_url = site_elements.eq(2);
			site_url.find('a:first').attr("name", "item_"+num+"_url_error");
			site_url.find('input:first').attr("id", "item_"+num+"_urlElement").attr("name", "item_"+num+"_url");
			num++;
		});
	}
});
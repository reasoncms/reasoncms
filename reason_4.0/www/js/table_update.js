/**
 * Sort rows without having to go to a new page.
 * By Henry Gross
 * May 18, 2006
 *
 * Modified by nathan white to work with reason admin
 *
 * - changes classes to accommodate even/odd coloring
 * - passes additional parameters to allow for integrity checking prior to relationship swap
 * - checks for status string of "success" before updating display
 * - added separate class designation for sort_switch_up vs sort_switch_down
 * 
 * May 14, 2008 
 *
 * Partially jquerified, also zapped the setTimeouts that would automatically follow the original link if javascript did not respond in time.
 *
 * October 31, 2011
 *
 * Sort up / down arrows were not refreshing properly. Fixed.
 */

$(document).ready(function()
{	
	var link;
	var link_disable = false;
	var reason_http_admin_images_path = get_reason_http_admin_images_path();
	create_event_handlers();

	/**
	 * Create the onclick events for all of the sorting links
	 */
	function create_event_handlers()
	{
		$("a[class^='sort_switch_']").click(sort);
	}
	
	/**
	 * Move a row
	 */
	function sort()
	{
	  var re, rowid, direction, rowid_switch;
	
	  link = $(this).attr("href");
	  clicked_image = $("img:first-child", this);
	  
	  re = /(.*?)do=move(.*?)&rowid=(.*)&eid=(.*)/;
	  direction = re.exec(link)[2];
	  rowid = Number(re.exec(link)[3]);
	  eid = Number(re.exec(link)[4]);
	
	  if (direction == "up") rowid_switch = rowid-1;
	  else if (direction == "down") rowid_switch = rowid+1;
	
	  if (!document.getElementById("row"+rowid_switch)) return true;
	  
	  if (link_disable == false) update_db(clicked_image, link, eid, rowid, rowid_switch);
	  return false;
	}
		
	/**
	 * XMLHttpRequest to send link thorugh tp update the database
	 */
	function update_db(image, link, eid, rowid, rowid_switch)
	{
		link_disable = true;
		orig_src = $(image).attr("src");
		$(image).attr("src", reason_http_admin_images_path + "wait.gif");
		$.get(link, { xmlhttp: "true" }, function(data)
		{
			if (data == "success")
			{
				$(image).attr("src", orig_src);
				status = change_dom(eid, rowid, rowid_switch);
				link_disable = false;
			}
			else
			{
				$(image).attr("src", orig_src);
				alert('There was an error alternating the sort order - please reload the page in your browser and try again.');
				link_disable = false;
			}
		});
		return true;
	}
	
	/**
	 * Change the DOM to display the change
	 */
	function change_dom(eid, rowid, rowid_switch)
	{
	  var row, row_switch, temp, links, links_switch
	  var links_cur_link, links_switch_cur_link
	  var del_direction, del_direction_switch, del_node, del_node_switch;
	  re = /(.*?)do=move(.*?)&rowid=(.*)/;
	  
	
	  temp = document.getElementById("row"+rowid).cloneNode(true);
	  row = document.getElementById("row"+rowid);
	  row_switch = document.getElementById("row"+rowid_switch);
	
	  
	//step one: just switch the rows
	  while (row.hasChildNodes())
		row.removeChild(row.firstChild);
	  while (row_switch.hasChildNodes())
		row.appendChild(row_switch.firstChild);
	  while (temp.hasChildNodes())
		row_switch.appendChild(temp.firstChild);
	  
	  //step two: create a move up/down as needed
	  links = row.getElementsByTagName("a");
	  links_switch = row_switch.getElementsByTagName("a");
	  lh1 = Array();
	  lh2 = Array();
	
	  for (var i=0; i<links.length; i++)
		if (links[i].getAttribute("class") == "sort_switch_up"
			|| links[i].getAttribute("className") == "sort_switch_up" 
			|| links[i].getAttribute("class") == "sort_switch_down" 
			|| links[i].getAttribute("className") == "sort_switch_down")
		  {
			links_cur_link = links[i].href;
			lh1.push (links[i]);
		  }
	
	  for (var i=0; i<links_switch.length; i++)
		if (links_switch[i].getAttribute("class") == "sort_switch_up"
		|| links_switch[i].getAttribute("className") == "sort_switch_up"
		|| links_switch[i].getAttribute("class") == "sort_switch_down" 
		|| links_switch[i].getAttribute("className") == "sort_switch_down")
		{
		  links_switch_cur_link = links_switch[i].href;
		  lh2.push (links_switch[i]);
		}
	   
	  links = lh1;
	  links_switch = lh2;
	
	  if (links.length==1)
	  {
		// extract entity id - eid should not change so we get what is current
		links_eid = links_cur_link.replace( /.*eid=([0-9]+).*/i,'$1' );
		links, direction = create_link(links, re, links_eid, rowid_switch);
		if (!links)
		  return false;
		del_direction = direction;
		del_node = "links_switch";
	  }
	  
	  if (links_switch.length==1)
	  {
		links_switch_eid = links_switch_cur_link.replace( /.*eid=([0-9]+).*/i,'$1' );
		links_switch, direction = create_link(links_switch, re, links_switch_eid, rowid);
		if (!links_switch)
		  return false;
		del_direction_switch = direction;
		del_node_switch = "links";
	  }
	
	//step three: delete a move up/down if not needed
	//and change the links to reflect the current position
	  links = row.getElementsByTagName("a");
	  links_switch = row_switch.getElementsByTagName("a");
	  
	  if (del_node_switch == "links")
		links = modify_links(links, re, true, del_direction_switch, rowid);
	  else
		links = modify_links(links, re, false, del_direction_switch, rowid);
	
	  if (del_node == "links_switch")
		links_switch = modify_links(links_switch, re, true, del_direction, rowid_switch);
	  else
		links_switch = modify_links(links_switch, re, false, del_direction, rowid_switch);
	
	//step four: update row colors - nwhite
	  change_row_color(row, row_switch);
	
	//step five: recreate the onclick handlers that have gotten lost in the process somehow (better yet do not lose them)
	  create_event_handlers();
	
	  return "";
	}
	
	/**
	 * Create a move up/down link
	 */
	function create_link(links, re, eid, rowid)
	{
	  new_sections = re.exec(links[0]);
	  new_link = new_sections[1];
	  if (new_sections[2]=="up")
		direction = "down";
	  else if (new_sections[2]=="down")
		direction = "up";
	  else
		return false
	  new_node = create_node(new_link, direction, eid, rowid);
	  if (new_node && direction=="up") {
		links[0].parentNode.insertBefore(new_node, links[0]);
		//links[0].parentNode.insertBefore(document.createTextNode(" baba "), links[0].nextSibling);
	  }
	  else if (new_node && direction=="down") {
		links[0].parentNode.insertBefore(new_node, links[0].nextSibling);
		//links[0].parentNode.insertBefore(document.createTextNode(" dada "), links[0].nextSibling);
	  }
	  return links, direction;
	}
	
	/**
	 * Create a new move up/down node
	 */
	function create_node(link, direction, eid, rowid)
	{
	  new_node = document.createElement("a");
	  new_node.href = link+"do=move"+direction+"&rowid="+rowid+"&eid="+eid;
	  if (direction == "up")
	  {
		new_node.setAttribute("class","sort_switch_up");
		new_node.setAttribute("className","sort_switch_up");
		new_image = document.createElement('img');
			new_image.setAttribute("alt", "move up");
		new_image.setAttribute("src", reason_http_admin_images_path + "arrow_up.gif");
	  }
	  else
	  {
		new_node.setAttribute("class","sort_switch_down");
		new_node.setAttribute("className","sort_switch_down");
		new_image = document.createElement('img');
			new_image.setAttribute("alt", "move down");
		new_image.setAttribute("src", reason_http_admin_images_path + "arrow_down.gif");
	  }
	  new_node.onclick=sort;
	  
	  new_text_node = document.createTextNode("move "+direction);
	  new_node.appendChild(new_image);
	  return new_node;
	}
	
	/**
	 * Lets look at this script and find the path up to /js/table_update.js.
	 */
	function get_reason_http_admin_images_path()
	{
		
		base_path = $('script[src$="table_update.js"]:first').attr("src").replace("/js/table_update.js","");
		return base_path + "/ui_images/reason_admin/";
	}
	
	/**
	 * Change the link of a row for the new position
	 * and delete a link if appropriate
	 */
	function modify_links(links, re, del_node, del_direction, rowid)
	{
	  for (var i=0; i<links.length; i++)
		if (links[i].getAttribute("class") == "sort_switch_up"
		  || links[i].getAttribute("className") == "sort_switch_up"
		  || links[i].getAttribute("class") == "sort_switch_down"
		  || links[i].getAttribute("className") == "sort_switch_down")
		{
		  
		  // extract entity id - eid should not change so we get what is current
		  cur_link = links[i].href;
		  eid = cur_link.replace( /.*eid=([0-9]+).*/i,'$1' );
		  
		  sections = re.exec(links[i]);
		  if (del_node==true){
			if (del_direction==sections[2]) {
			  links[i].parentNode.removeChild(links[i]);
			  i--;
			}
			else
			  links[i].href = sections[1]+"do=move"+sections[2]+"&rowid="+rowid+"&eid="+eid;
		  }
		  else
			links[i].href = sections[1]+"do=move"+sections[2]+"&rowid="+rowid+"&eid="+eid;
		}
	  return links;
	}
	
	/**
	 * Change the class designation of a table data cell
	 * so that even / odd row coloring is preserved
	 * @author nwhite
	 */
	function change_row_color(row1, row2)
	{
		if (      row1.getAttribute("class") == "listRow1"
			   || row1.getAttribute("className") == "listRow1")
		{
			row1_class = "listRow1";
			row2_class = "listRow2";
		}
		else
		{
			row1_class = "listRow2";
			row2_class = "listRow1";
		}
		
		row1.setAttribute("class",row1_class);
		row1.setAttribute("className",row1_class);
		row2.setAttribute("class",row2_class);
		row2.setAttribute("className",row2_class);
	}
});
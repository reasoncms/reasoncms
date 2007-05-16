/**
 * Javascript to load in the state of all forms when the page is loaded,
 * and when a link is clicked, it checks all forms against these
 * and prompts the user to save them if desired
 * @author Henry gross
 * @modified May 7, 2007
 */

var original_state;
var iframes;
var req;
var IGNORE_CLASS = "class to ignore";

/**
 * Add eventlisteners to the links to have then check the states of the fields
 */
function add_checks()
{
  var links;
  links = document.getElementsByTagName("a");
  for (var i=0; i<links.length; i++)
    if (links[i].getAttribute("href") != "" && !links[i].className.match(IGNORE_CLASS))
      links[i].onclick=check;
}

/**
 * A javascript version of a html form element
 * or at least the parts that we care about
 */
function Form(dom_form)
{
  var name;
  var fields;
  var method;
  var action;

  /**
   * An input element
   */
  function Input(dom_input)
  {
    var dom_type;
    var name;
    var value;
    var type;
    var checked;
    var disabled;


    this.dom_type = "input";
    
    if (typeof(dom_input) != "undefined")
    {
      this.name = dom_input.name;
      this.value = dom_input.value;
      this.type = dom_input.type;
      this.checked = dom_input.checked;
      this.disabled = dom_input.disabled;
    }
  }
  
  /**
   * Test to see if this element is the same as some other element
   * @param other_input the input element to compare this element against
   * @return true if the elements are equal
   */
  Input.prototype.equals = function(other_input)
  {
    if (other_input == undefined)
      return true;
    if (this.name != other_input.name)
      return false;
    else if (this.value != other_input.value)
      return false;
    else if (this.type != other_input.type)
      return false;
    else if (this.checked != other_input.checked)
      return false;
    else if (this.disabled != other_input.disabled)
      return false;
    else
      return true;
  }

  /**
   * Return the query string that would be used to submit this input
   * @return the query string that would be used to submit this input
   */
  Input.prototype.queryString = function()
  {
    var query_string;
    query_string = "";
    if (!this.disabled)
    {
      if ((this.type != "radio" && this.type != "checkbox") || this.checked)
      {
        query_string = urlencode(this.name) + "=" + urlencode(this.value);
      }
    }
    return query_string;
  }
  
  /**
   * A select element
   */
  function Select(dom_select)
  {
    var dom_type;
    var options;
    var name;
    var disabled;

    /**
     * An option element
     */
    function Option(dom_option)
    {
      var selected;
      var disabled;
      var value;

      if (typeof(dom_option) != "undefined")
      {
        this.selected = dom_option.selected;
        this.disabled = dom_option.disabled;
        this.value = dom_option.firstChild.nodeValue;
      }
    }

    /**
     * Test to see if this element is the same as some other element
     * @param other_option the option element to compare this element against
     * @return true if the elements are equal
     */
    Option.prototype.equals = function(other_option)
    {
      if (other_option == undefined)
        return true;
      if (this.selected != other_option.selected)
        return false;
      else if (this.disabled != other_option.disabled)
        return false;
      else if (this.value != other_option.value)
        return false;
      else
        return true;
    }

    this.dom_type = "select";
    this.options = new Array();

    if (typeof(dom_select) != "undefined")
    {
      this.name = dom_select.name;
      var option = dom_select.getElementsByTagName("option");
      for (var i = 0; i < option.length; i++)
        this.options.push(new Option(option[i]));
    }
  }
  
  /**
   * Test to see if this element is the same as some other element
   * @param other_select the select element to compare this element against
   * @return true if the elements are equal
   */
  Select.prototype.equals = function(other_select)
  {
    if (other_select == undefined)
      return true;
    if (this.name != other_select.name)
      return false;
    else if (this.disabled != other_select.disabled)
      return false
    else if (this.options.length != other_select.options.length)
      return false;
    else
    {
      var eq;
      this.eq = true;
      for (var i = 0; i < this.options.length && this.eq; i++)
      {
        if (!this.options[i].equals(other_select.options[i]))
          this.eq = false;
      }
      return this.eq;
    }
  }
  
  /**
   * Return the query string that would be used to submit this select
   * @return the query string that would be used to submit this select
   */
  Select.prototype.queryString = function()
  {
    var query_string;
    query_string = "";
    if (!this.disabled)
    {
      for (var i = 0; i < this.options.length; i++)
      {
        if (!this.options[i].disabled && this.options[i].selected)
        {
          query_string += urlencode(this.name) + "=" +
            urlencode(this.options[i].value) + "&";
        }
      }
    }
    return query_string;
  }
  
  /**
   * A textarea element
   */
  function TextArea(dom_textarea)
  {
    var dom_type;
    var name;
    var disabled;
    var value;

    this.dom_type = "textarea";
   
    if (typeof(dom_textarea))
    {
      this.name = dom_textarea.name;
      this.disabled = dom_textarea.disabled;
      this.value = dom_textarea.value;
    }
  }

  /**
   * Test to see if this element is the same as some other element
   * @param other_textarea the textarea element to compare this element against
   * @return true if the elements are equal
   */
  TextArea.prototype.equals = function(other_textarea)
  {
    if (other_textarea == undefined)
      return true;
    if (this.name != other_textarea.name)
      return false;
    else if (this.disabled != other_textarea.disabled)
      return false;
    else if (this.value != other_textarea.value)
      return false;
    return true;
  }

  /**
   * Return the query string that would be used to submit this textarea
   * @return the query string that would be used to submit this textarea
   */
  TextArea.prototype.queryString = function()
  {
    var query_string;
    query_string = "";
    if (!this.disabled)
    {
      query_string = urlencode(this.name) + "=" + urlencode(this.value);
    }
    return query_string;
  }
  
  if (typeof(dom_form) != "undefined")
  {
    this.name = dom_form.name;
    this.action = dom_form.action;
    this.method = dom_form.method;
    
    this.fields = new Array();
    
    var inputs = dom_form.getElementsByTagName("input");
    for (var i = 0; i < inputs.length; i++)
    {
      var input = new Input(inputs[i]);
      this.fields.push(input);
    }
    var selects = dom_form.getElementsByTagName("select");
    for (var i = 0; i < selects.length; i++)
    {
      var select = new Select(selects[i]);
      this.fields.push(select);
    }
    var textareas = dom_form.getElementsByTagName("textarea");
    for (var i = 0; i < textareas.length; i++)
    {
      var textarea = new TextArea(textareas[i]);
      this.fields.push(textarea);
    }
  }
}

/**
 * Test to see if this form is the same as some other form
 * @param other_form the form to compare this form against
 * @return true if the elements are equal
 */
Form.prototype.equals = function(other_form)
{
  var eq;
  this.eq = true;
  for (var i = 0; i < this.fields.length && this.eq; i++)
  {
    var found = false;
    for (var j = 0; j < other_form.fields.length && !found; j++)
    {
      if (this.fields[i].name == other_form.fields[j].name)
      {
        found = true;
        if (!this.fields[i].equals(other_form.fields[j]))
          this.eq = false;
      }
    }
    if (!found)
      this.eq = false;
  }
  return this.eq;
}

/**
 * Submit this form all DHTML-y
 */
/*Form.prototype.submit = function()
{
  var query_string;
  query_string = this.queryString();
  if (this.method.toUpperCase() == "POST")
  {
    req.open("POST", this.action, true);
    req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    req.send(query_string);
  }
  else if (this.method.toUpperCase() == "GET")
  {
    req.open("GET", this.action + "?" + query_string, true);
    req.send(null);
  }
}*/

/**
 * Returns the query string for this form
 * @return the query string for this form
 */
Form.prototype.queryString = function()
{
  var query_string;
  query_string = ""
  for (var i = 0; i < this.fields.length; i++)
    query_string += this.fields[i].queryString() + "&";
  query_string = query_string.replace(/&+/g, "&");
  query_string = query_string.replace(/&$/, "");
  return query_string;
}

/**
 * Encode a string for a url
 * @param string the string to be encoded
 * @return the encoded string
 */
function urlencode(string)
{
  string = string.replace(/&/g, "%26");
  string = string.replace(/=/g, "%3D");
  string = string.replace(/\//g, "%2F");
  return string;
}

/**
 * Go through all of the forms on the page
 * and create an array to contain them all
 * @return an array containing the froms on the page
 */
function load_forms()
{
  var forms;
  var states = new Array();
  forms = document.getElementsByTagName("form");
  for (var i=0; i<forms.length; i++)
  {
    var form = new Form(forms[i]);
    states[form.name] = form;
  }
  return states;
}

/**
 * Go through all of the iframes on the page
 * and create an array of all of their innerHTMLs indexed on their id
 * @return an array containing the iframes on the page
 */
function load_iframes()
{
  var wysiwyg;
  var arr;
  var index;
  wysiwyg = document.getElementsByTagName("iframe");
  arr = new Array();

  for (var i = 0; i < wysiwyg.length; i++)
  {
    var obj;
    var frame_doc;
    var html;
    frame_doc = wysiwyg[i].contentWindow.document;
    html = frame_doc.getElementsByTagName("body")[0].innerHTML;
    obj = new Array();
    obj["element"] = wysiwyg[i];
    obj["html"] = html;
    arr.push(obj);
  }
  return arr;
}

/**
 * Check to see if any forms changed, and if they did,
 * ask the user if they want to submit them
 */
function check()
{
  var new_state;
  var confirm_change;
  var confirm_message;
  var answered;
  var stay;

  new_state = load_forms();
  confirm_message = "This form contains edits.\n\n" + 
       				"Click \"Cancel\" to stay on this form.\n\n" +
					"Click \"OK\" to exit this form without saving your changes.";
  stay = false;
  answered = false;

  for (var state in original_state)
  {
    if (!answered && !original_state[state].equals(new_state[state]))
    {
      confirm_change = confirm(confirm_message);
      if (!confirm_change)
      {
        stay = true;
        //new_state[state].submit();
        //alert("Your form has been submitted");
      }
      answered = true;
    }
  }

  for (var iframe in iframes)
  {
    var elem_doc;
    var old_html;
    var new_html;
    elem_doc = iframes[iframe]["element"].contentWindow.document;
    old_html = iframes[iframe]["html"]
    new_html = elem_doc.getElementsByTagName("body")[0].innerHTML;
   
    if (!answered && elem_doc.designMode.toLowerCase() == "on" &&
      old_html != new_html)
    {
      confirm_change = confirm(confirm_message);
      if (!confirm_change)
      {
        stay = true;
      }
      answered = true;
    }
  }
  return !stay;
}

/**
 * Loads the forms and iframes into the global variables
 */
function load_original_state()
{
  original_state = load_forms();
  iframes = load_iframes();
}

/**
 * Adds event listeners to all the links, loads the initial state of the forms,
 * and creates an XMLHTTPRequest object
 */
function init()
{
  add_checks();
  setTimeout(load_original_state, 500);

/*  if (window.ActiveXObject)
  {
    try {
      req = new ActiveXObject("Msxml2.XMLHTTP");
    } catch(e) {
      try {
        req = new ActiveXObject("Microsoft.XMLHTTP");
      } catch(e) {}
    }
  }
  else if (window.XMLHttpRequest)
  {
    try {
      req = new XMLHttpRequest();
    } catch(e) {}
  }*/

  //req.onreadystatechange = debugResponse;
}

/**
 * Function used to check to repsonse from the server
 * For some reason Firefox 1.5 doesn't like this and locks up the browser,
 * so good thing it is just debugging and is never called
 */
function debugResponse() {
  if (req.readyState == 4)
    alert(req.responseText);
}

if (window.addEventListener)
  window.addEventListener("load", init, true);
else if (window.attachEvent)
  window.attachEvent("onload", init);

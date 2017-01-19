localPrettyName = "Dropdown"

Formbuilder.registerField 'dropdown',

  order: 24

  view: """
    <select disabled>
      <% if (rf.get(Formbuilder.options.mappings.INCLUDE_BLANK)) { %>
        <option value=''></option>
      <% } %>

      <%
        var optionsForLooping = rf.get(Formbuilder.options.mappings.OPTIONS) || [];
        for (var i = 0 ; i < optionsForLooping.length ; i++) {
      %>
        <option <%= rf.get(Formbuilder.options.mappings.OPTIONS)[i].checked && 'selected' %>>
          <%= Formbuilder.helpers.warnIfEmpty(rf.get(Formbuilder.options.mappings.OPTIONS)[i].label, Formbuilder.options.dict.EMPTY_OPTION_WARNING) %>
        </option>
      <% } %>
    </select>

    <% if (optionsForLooping.length == 0) { %>
        <%= Formbuilder.helpers.warnIfEmpty("", Formbuilder.options.dict.EMPTY_OPTION_LIST_WARNING) %>
    <% } %>
  """

  edit: """
    <%= Formbuilder.templates['edit/options']() %>
  """

  ###was:  """
    <%= Formbuilder.templates['edit/options']({ includeBlank: true }) %>
  """###

  instructionDetails: """
    <div class="instructionText">Used when you want the user to select one (and only one) option from a pre-populated list.</div>
    <div class="instructionExample">What is your major?<br><select><option>Biology</option></select></div>
  """

  prettyName: localPrettyName
  addButton: "<span class='symbol'><span class='form-elements-icon form-elements-icon-dropdown'></span></span> " + localPrettyName

  defaultAttributes: (attrs) ->
    _.pathAssign(attrs, Formbuilder.options.mappings.OPTIONS, Formbuilder.generateDefaultOptionsArray())
    _.pathAssign(attrs, Formbuilder.options.mappings.INCLUDE_BLANK, false)

    attrs

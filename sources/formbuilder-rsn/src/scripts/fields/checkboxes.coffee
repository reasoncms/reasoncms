localPrettyName = "Checkboxes"

Formbuilder.registerField 'checkboxes',

  order: 10

  view: """
    <%
        var optionsForLooping = rf.get(Formbuilder.options.mappings.OPTIONS) || [];
        for (var i = 0 ; i < optionsForLooping.length ; i++) {
    %>
      <div>
        <label class='fb-option'>
          <input type='checkbox' <%= rf.get(Formbuilder.options.mappings.OPTIONS)[i].checked && 'checked' %> onclick="javascript: return false;" />
          <%= Formbuilder.helpers.warnIfEmpty(rf.get(Formbuilder.options.mappings.OPTIONS)[i].label, Formbuilder.options.dict.EMPTY_OPTION_WARNING) %>
        </label>
      </div>
    <% } %>

    <% if (optionsForLooping.length == 0) { %>
        <%= Formbuilder.helpers.warnIfEmpty("", Formbuilder.options.dict.EMPTY_OPTION_LIST_WARNING) %>
    <% } %>

    <% if (rf.get(Formbuilder.options.mappings.INCLUDE_OTHER)) { %>
      <div class='other-option'>
        <label class='fb-option'>
          <input type='checkbox' />
          Other
        </label>

        <input type='text' />
      </div>
    <% } %>
  """

  edit: """
    <%= Formbuilder.templates['edit/options']() %>
  """

  ###was: """
    <%= Formbuilder.templates['edit/options']({ includeOther: true }) %>
  """###

  instructionDetails: """
    <div class="instructionText">Used when you want the user to select any number of options from a pre-populated list.</div>
    <div class="instructionExample">What sports do you enjoy?<br>
      <input type="checkbox"> Basketball<br>
      <input type="checkbox"> Football<br>
      <input type="checkbox"> Soccer<br>
      <input type="checkbox"> Ultimate Frisbee<br>
      <input type="checkbox"> Volleyball<br>
    </div>
  """

  prettyName: localPrettyName
  addButton: "<span class=\"symbol\"><span class=\"fa fa-check-square-o\"></span></span> " + localPrettyName

  defaultAttributes: (attrs) ->
    _.pathAssign(attrs, Formbuilder.options.mappings.OPTIONS, Formbuilder.generateDefaultOptionsArray())

    attrs

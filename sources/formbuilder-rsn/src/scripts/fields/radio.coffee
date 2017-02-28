localPrettyName = "Radio Buttons"

Formbuilder.registerField 'radio',

  order: 15

  view: """
    <%
      var optionsForLooping = rf.get(Formbuilder.options.mappings.OPTIONS) || [];
      for (var i = 0 ; i < optionsForLooping.length ; i++) {
    %>
      <div>
        <label class='fb-option'>
          <input type='radio' <%= rf.get(Formbuilder.options.mappings.OPTIONS)[i].checked && 'checked' %> onclick="javascript: return false;" />
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
          <input type='radio' />
          Other
        </label>

        <input type='text' />
      </div>
    <% } %>
  """

  edit: """
    <%= Formbuilder.templates['edit/options']() %>
  """
  ### was: """
    <%= Formbuilder.templates['edit/options']({ includeOther: true }) %>
  """###

  instructionDetails: """
    <div class="instructionText">Used when you want the user to select one (and only one) option from a pre-populated list.</div>
    <div class="instructionExample">Do you have a driver's license?<br>
      <input type="radio"> Yes<br>
      <input type="radio"> No<br>
    </div>
  """

  prettyName: localPrettyName
  addButton: "<span class=\"symbol\"><span class=\"fa fa-circle-o\"></span></span> " + localPrettyName

  defaultAttributes: (attrs) ->
    _.pathAssign(attrs, Formbuilder.options.mappings.OPTIONS, Formbuilder.generateDefaultOptionsArray())

    attrs

localPrettyName = "Short Text"
Formbuilder.registerField 'text',

  order: 0

  view: """
    <input disabled type='text' class='rf-size-<%= rf.get(Formbuilder.options.mappings.SIZE) %>'/>
  """

  edit: """
    <%= Formbuilder.templates['edit/defaultVal']() %>
  """
  ###was: """
    <%= Formbuilder.templates['edit/size']() %>
    <%= Formbuilder.templates['edit/min_max_length']() %>
  """###

  instructionDetails: """
    <div class="instructionText">Used to gather short amounts of free-form text input from a user.</div>
    <div class="instructionExample">Name:<br><input type="text"></div>
  """

  prettyName: localPrettyName
  # addButton: "<span class='symbol'><span class='fa fa-font'></span></span> " + localPrettyName
  addButton: "<span class='symbol'><span class='form-elements-icon form-elements-icon-short-text'></span></span> " + localPrettyName

  # defaultAttributes: (attrs) ->
  #   _.pathAssign(attrs, Formbuilder.options.mappings.SIZE, 'small')
  #
  #   attrs
#

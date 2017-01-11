localPrettyName = "Multiline Text"

Formbuilder.registerField 'paragraph',

  order: 5

  view: """
    <textarea disabled class='rf-size-<%= rf.get(Formbuilder.options.mappings.SIZE) %>'></textarea>
  """

  edit: """
    <%= Formbuilder.templates['edit/defaultVal']() %>
  """

  ###was: """
    <%= Formbuilder.templates['edit/size']() %>
    <%= Formbuilder.templates['edit/min_max_length']() %>
  """###

  ###
  addButton: """
    <span class="symbol">&#182;</span> Paragraph
  """
  ###

  instructionDetails: """
    <div class="instructionText">Used to gather longer amounts of free-form text input from a user.</div>
    <div class="instructionExample">Explain why you are the best candidate for this position:
      <br>
      <textarea rows=5 cols=30></textarea>
    </div>
  """

  prettyName: localPrettyName
  addButton: "<span class='symbol'><span class='form-elements-icon form-elements-icon-long-text-2'></span></span> " + localPrettyName

  # defaultAttributes: (attrs) ->
  #   _.pathAssign(attrs, Formbuilder.options.mappings.SIZE, 'small')
  #
  #   attrs
#

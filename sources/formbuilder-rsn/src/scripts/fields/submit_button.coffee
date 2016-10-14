Formbuilder.registerField 'submit_button',

  order: 20

  type: 'non_input'

  view: """
    <label class="preview-only">Submit Button</label>
    <button disabled><%= rf.get(Formbuilder.options.mappings.LABEL) %></button>
  """

  edit: """
    <div class='fb-edit-section-header'>Button Label</div>
    <input type="text" data-rv-input='model.<%= Formbuilder.options.mappings.LABEL %>'></input>
  """

  addButton: """
    <span class='symbol'><span class='fa fa-inbox'></span></span> Submit Button
  """

  defaultAttributes: (attrs) ->
    _.pathAssign(attrs, Formbuilder.options.mappings.LABEL, 'Submit')

    attrs

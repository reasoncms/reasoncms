localPrettyName = "Hidden Field"
Formbuilder.registerField 'hidden_field',

  order: 10

  type: 'non_input'

  view: """
    <label class="preview-only">#{localPrettyName}</label>
    <label class='section-name'><%= rf.get(Formbuilder.options.mappings.LABEL) %></label>
    <pre><code><%= _.escape(rf.get(Formbuilder.options.mappings.DESCRIPTION)) %></code></pre>
  """

  edit: """
    <div class='fb-label-description'>
      <div class='fb-edit-section-header'>Label</div>
      <input type='text' data-rv-input='model.<%= Formbuilder.options.mappings.LABEL %>' />
      <div class='fb-edit-section-header'>Data</div>
      <textarea data-rv-input='model.<%= Formbuilder.options.mappings.DESCRIPTION %>'
        placeholder='Add some data to this hidden field'></textarea>
    </div>
  """

  instructionDetails: """
    <div class="instructionText">Used to pass data through the form without displaying it to the user.</div>
  """


  prettyName: localPrettyName
  addButton: "<span class='symbol'><span class='fa fa-code'></span></span> " + localPrettyName

  defaultAttributes: (attrs) ->
    _.pathAssign(attrs, Formbuilder.options.mappings.LABEL, 'Hidden Field')

    attrs

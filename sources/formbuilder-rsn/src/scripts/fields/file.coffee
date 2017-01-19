localPrettyName = "File"

Formbuilder.registerField 'file',

  order: 55

  view: """
    <input type='file' />
  """

  edit: """
    <%= Formbuilder.templates['edit/file_restrictions']() %>
  """

  # addButton: """
    # <span class="symbol"><span class="fa fa-cloud-upload"></span></span> File
  # """
  addButton: "<span class='symbol'><span class='fa fa-cloud-upload'></span></span> " + localPrettyName

  instructionDetails: """
    <div class="instructionText">Provides a way for a user to upload a file.</div>
  """

  prettyName: localPrettyName

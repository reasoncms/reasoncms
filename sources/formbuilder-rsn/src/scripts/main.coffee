emptyOrWhitespaceRegex = RegExp(/^\s*$/)
DELETE_KEYCODE = 8
ENTER_KEYCODE = 13


class FormbuilderModel extends Backbone.DeepModel
  sync: -> # noop
  indexInDOM: ->
    $wrapper = $(".fb-field-wrapper").filter ( (_, el) => $(el).data('cid') == @cid  )
    $(".fb-field-wrapper").index $wrapper
  is_input: ->
    Formbuilder.inputFields[@get(Formbuilder.options.mappings.FIELD_TYPE)]?
  is_last_submit: ->
    # if the model is last and is a submit button
    (@collection.length - @collection.indexOf(@)) is 1 and @get(Formbuilder.options.mappings.FIELD_TYPE) is 'submit_button'

  constructor: () ->
    super

    if (@attributes.cid)
      # this was previously constructed. If there's a problem, it was handled via performInitialUniqueIdPass
    else
      # this is being created on the fly - either automatically (like a submit button) or
      # via user interaction by adding a field. Either way we don't trust the cid that was automatically assigned so let's
      # overwrite it. It'll get copied into @attributes.cid via copyCidToModel in a second.
      @cid = Formbuilder.getNextUniqueGlobalId("c")

class FormbuilderCollection extends Backbone.Collection
  initialize: ->
    @on 'add', @copyCidToModel

  model: FormbuilderModel

  comparator: (model) ->
    model.indexInDOM()

  copyCidToModel: (model) ->
    model.attributes.cid = model.cid

# Classes for history
class DeletedFieldModel extends Backbone.DeepModel
  sync: -> # noop

class DeletedFieldCollection extends Backbone.Collection
  model: DeletedFieldModel

class ViewFieldView extends Backbone.View
  className: "fb-field-wrapper"

  events:
    'click .subtemplate-wrapper .cover': 'focusEditView'
    'click .js-duplicate': 'duplicate'
    'click .js-clear': 'clear'

  initialize: (options) ->
    {@parentView} = options
    @listenTo @model, "change", @render
    @listenTo @model, "remove", @remove

  render: ->
    @$el.addClass('response-field-' + @model.get(Formbuilder.options.mappings.FIELD_TYPE))
        .data('cid', @model.cid)
        .html(Formbuilder.templates["view/base#{
          if @model.is_last_submit() then '_no_duprem'
          else if !@model.is_input() then '_non_input'
          else ''
          }"]({rf: @model}))

    return @

  focusEditView: ->
    @parentView.createAndShowEditView(@model)

  clear: ->
    @parentView.handleFormUpdate()
    @parentView.deleteToStack(@model)
    #@model.destroy()

  duplicate: ->
    attrs = _.clone(@model.attributes)
    delete attrs['id']
    attrs['label'] += ' Copy'
    @parentView.createField attrs, { position: @model.indexInDOM() + 1 }


class EditFieldView extends Backbone.View
  className: "edit-response-field"

  events:
    'click .js-add-option': 'addOption'
    'click .js-remove-option': 'removeOption'
    'click .js-default-updated': 'defaultUpdated'
    'input .option-label-input': 'forceRender'
    'keydown .option-label-input': 'handleSpecialKeysDuringOptionEditing'

  handleSpecialKeysDuringOptionEditing: (evt) ->
    if true and (evt.which == DELETE_KEYCODE or evt.keyCode == DELETE_KEYCODE)
      currLabel = evt.currentTarget.value
      if (currLabel == "")
        deletionIndex = $(evt.currentTarget).parent().index()
        @removeOptionAtIndex deletionIndex

        # focus on another option
        focusIndex = (if deletionIndex == 0 then 0 else deletionIndex - 1)
        newFocusFields = $(".edit-response-field .sortableParentContainer .option .option-label-input")
        if (newFocusFields.length > 0)
          (newFocusFields[focusIndex]).focus()

        return false
    else if evt.which == ENTER_KEYCODE or evt.keyCode == ENTER_KEYCODE
      if (navigator.userAgent.match(/(iPad|iPhone|iPod touch)/i))
        return

      @addOption evt

    # console.log evt
    # console.log this

  initialize: (options) ->
    {@parentView} = options
    @listenTo @model, "remove", @remove

  render: ->
    # special case - only show the "default value" UI if user has previously put data in for it
    dvalIsEmpty = Formbuilder.helpers.fieldIsEmptyOrNull(@model.get(Formbuilder.options.mappings.DEFAULT_VALUE))
    # stuff a val into the model so that rivets can render appropriately
    @model.attributes.displayDefaultValueUI = (not dvalIsEmpty)

    @$el.html(Formbuilder.templates["edit/base#{if !@model.is_input() then '_non_input' else ''}"]({rf: @model}))
    rivets.bind @$el, { model: @model }

    # if it's type with options, we also need to turn on JQuery sortability. This needs a slight delay to function else the JQuery selector comes back empty
    if (@model.attributes.field_type in ["radio", "dropdown", "checkboxes"])
      setTimeout((=>
        $(".sortableParentContainer").sortable({
                                                axis: "y",
                                                start: ((evt, ui) -> ui.item.preservedStartPos = ui.item.index()),
                                                stop: ((evt, ui) => @completedOptionDrag(evt, ui)),
                                                handle: ".js-drag-handle"})
      ), 10)

    allowTypeChange = Formbuilder.options.ALLOW_TYPE_CHANGE
    if (@model.attributes.field_type == "submit_button")
      allowTypeChange = false
    setTimeout((=>
      if (allowTypeChange)

        $("#fieldDisplayEditable").css("display", "block")

        # now we need to do some setup on the "fieldTypeSelector" dropdown...first highlight the relevant option
        $("#fieldTypeSelector").val(@model.attributes.field_type)

        # and now listen for changes
        $("#fieldTypeSelector").change((=>
          fromType = @model.attributes.field_type
          toType = $("#fieldTypeSelector").val()
          @changeEditingFieldTypeWithDataLossWarning(fromType, toType)
        ))

        $("#fieldDisplayNonEditable").remove()
      else
        $("#fieldDisplayNonEditable").css("display", "block")
        $("#fieldDisplayEditable").remove()

    ), 10)

    # and another one - focus the "Label" field if it's empty
    setTimeout((=>
      if (Formbuilder.helpers.fieldIsEmptyOrNull(@model.get(Formbuilder.options.mappings.LABEL)))
        $(".fb-label-description input").focus()
    ), 10)

    return @

  dataWasEntered: (data) ->
    console.log "checking data [" + data + "]..."
    if (data != null and data != undefined and data != "")
      return true
    else
      return false
    

  # converting from one field to another may cause some loss of data. This checks to see
  # if we're in such a situation, and warns the user if so.
  changeEditingFieldTypeWithDataLossWarning: (fromType, toType) ->
    if (fromType == toType)
      return

    multiFields = ["radio","checkboxes","dropdown"]

    warning = ""

    if (fromType in ["text", "paragraph"])
      # text/paragraph types have a "default value" - if this is present, and we're switching to certain types, need to warn...
      if (toType not in ["text", "paragraph"])
        inputData = @model.get(Formbuilder.options.mappings.DEFAULT_VALUE)
        if (@dataWasEntered(inputData))
          warning = "you will lose the default value text \"" + inputData + "\""
    else if (fromType == "text_comment")
      # every translation is ok
    else if (fromType == "hidden_field")
      inputData = @model.get(Formbuilder.options.mappings.DESCRIPTION)
      if (@dataWasEntered(inputData))
        warning = "you will lose the data text \"" + inputData + "\""
    else if (fromType in multiFields)
      # are there any options, and how many are checked?
      numOptions = 0
      numCheckedOptions = 0
      if (@model.get(Formbuilder.options.mappings.OPTIONS))
        for o in @model.get(Formbuilder.options.mappings.OPTIONS)
          numOptions++
          if (o.checked)
            numCheckedOptions++

      if (toType in multiFields)
        if (fromType == "checkboxes" and numCheckedOptions > 1)
          warning = "only one option can be checked by default"
      else
        if (numOptions > 0)
          warning = "you will lose all your entered options"
    else
      console.log "change_type from [" + fromType + "] to [" + toType + "] is not supported"
      $("#fieldTypeSelector").val(fromType)
      return

    if (warning == "")
      @changeEditingFieldType(fromType, toType)
    else
      prettyFrom = if Formbuilder.fields[fromType].prettyName then Formbuilder.fields[fromType].prettyName else fromType
      prettyTo = if Formbuilder.fields[toType].prettyName then Formbuilder.fields[toType].prettyName else toType

      warning = "Warning - by changing this field from \"" + prettyFrom + "\" to \"" + prettyTo + "\", " + warning + ". Are you sure you want to do this? This cannot be undone!"

      if (confirm(warning))
        @changeEditingFieldType(fromType, toType)
      else
        $("#fieldTypeSelector").val(fromType)

  changeEditingFieldType: (fromType, toType) ->
    ###
    other possibility - in fields/[input_type].coffee, fields that require custom behavior can define functions like:
          getDataForTranslation: ((model) ->
            return { label: model.get(Formbuilder.options.mappings.DESCRIPTION) }
            )

          setDataForTranslation: ((model, translationData) ->
            model.set(Formbuilder.options.mappings.LABEL, "Text Comment")
            model.set(Formbuilder.options.mappings.DESCRIPTION, translationData.label)
            )
    
    and then this function could hook into it thusly:
          if (Formbuilder.fields[fromType].getDataForTranslation)
            # some fields store their data in non-standard ways. Grab it from them if possible
            translationData = Formbuilder.fields[fromType].getDataForTranslation(@model)

    problem is since those individual coffee files for the field types aren't really classes, we lose a lot of 
    the benefits of this approach - can't do real base class functionality, so this logic would end up mixed between
    those individual coffee files and this function for default behavior.

    At some point might be nice to rethink how those fields register themselves, but for now we can
    contain the logic to this one function at least, so it's manageable.
    ###

    translationData = { pseudoLabel: null, options: null, defaultValue: null }
    if (fromType in ["text_comment"])
      translationData.pseudoLabel = @model.get(Formbuilder.options.mappings.DESCRIPTION)
    else
      translationData.pseudoLabel = @model.get(Formbuilder.options.mappings.LABEL)

    if (fromType in ["text", "paragraph"] and toType in ["text", "paragraph"])
      translationData.defaultValue = @model.get(Formbuilder.options.mappings.DEFAULT_VALUE)

    if (toType in ["radio", "checkboxes", "dropdown"])
      if (fromType in ["radio", "checkboxes", "dropdown"])
        # checkboxes allow multiple prechecks; radio/dropdown do not. If we're moving from checkboxes, need to make sure no more than one thing is checked...
        onlyAllowOneCheck = fromType == "checkboxes"
        if (onlyAllowOneCheck)
          checksSeen = 0
          if (@model.get(Formbuilder.options.mappings.OPTIONS))
            for o, idx in @model.get(Formbuilder.options.mappings.OPTIONS)
              if (o.checked)
                if (checksSeen > 0)
                  o.checked = false
                checksSeen++

        translationData.options = _.clone(@model.get(Formbuilder.options.mappings.OPTIONS))


      # when we switch to one of the multiple choice field types, let's add some default options
      if (!translationData.options or translationData.options.length == 0)
        translationData.options = Formbuilder.generateDefaultOptionsArray()

    # console.log "label set to [" + translationData.pseudoLabel + "]"

    # we've extracted the relevant data; now let's update the model
    # delete stuff that may not exist on the destination type; it'll get re-created if necessary
    delete @model.attributes.field_options
    delete @model.attributes.default_value

    # everything has a field type
    @model.set(Formbuilder.options.mappings.FIELD_TYPE, toType)

    # most things store label in label, but text comment is a little screwy
    if (toType in ["text_comment"])
      @model.set(Formbuilder.options.mappings.LABEL, Formbuilder.fields[toType].defaultAttributes({})[Formbuilder.options.mappings.LABEL])
      @model.set(Formbuilder.options.mappings.DESCRIPTION, translationData.pseudoLabel)
    else
      @model.set(Formbuilder.options.mappings.LABEL, translationData.pseudoLabel)

    if (translationData.defaultValue != null)
      @model.set(Formbuilder.options.mappings.DEFAULT_VALUE, translationData.defaultValue)

    if (translationData.options != null)
      @model.set(Formbuilder.options.mappings.OPTIONS, translationData.options)

    @forceRender() # re-renders the right-hand-side of page
    @parentView.createAndShowEditView(@model, true) # updates the edit view
        
  
  debugOptions: (opts) ->
    rv = ""
    for o in opts
      if rv isnt ""
        rv += ","
      rv += o.label

    rv += " (#{opts.length} elements)"
    rv

  completedOptionDrag: (evt, ui) ->
    [oldIdx,newIdx] = [ui.item.preservedStartPos,ui.item.index()]
    # console.log "completed drag from [" + oldIdx + "] to [" + newIdx + "]"

    ###
    this is the funky part. I think the options template (which is a combination of Backbone and Rivets tech) and the JQuery DOM
    manipulation are stomping on each other. Below I am going to update the OPTIONS model and trigger the appropriate events,
    but this was causing weird behavior -- the right hand side of the page would show the correct new order, but the left hand side
    was out of wack. So, now we'll just use JQuery sortable up to this point -- we capture the old/new indices of the item we dragged,
    and then we cancel JQuery's work completely. THEN we'll update the model ourselves using that data, and notify interested parties.
    
    Maybe there is some way to keep JQuery/Rivets/Backbone in sync with one another but with basically zero knowledge of how the latter
    two of those three pieces of software function that is a slog of a debugging process and this works just fine.
    ###
    $(".sortableParentContainer").sortable('cancel')

    options = @model.get(Formbuilder.options.mappings.OPTIONS)
    if (oldIdx isnt newIdx)
      mover = options.splice(oldIdx, 1)[0]
      options.splice(newIdx, 0, mover)

    @model.set Formbuilder.options.mappings.OPTIONS, options          # this line updates the model
    @model.trigger "change:#{Formbuilder.options.mappings.OPTIONS}"   # this line re-renders the template controlling the left hand side interface
    @forceRender()                                                    # this line re-renders the right hand side representation of the model

  remove: ->
    @parentView.editView = undefined
    @parentView.$el.find("[data-target=\"#addField\"]").click()
    super

  # @todo this should really be on the model, not the view
  addOption: (e) ->
    $el = $(e.currentTarget)
    i = @$el.find('.option').index($el.closest('.option'))
    options = @model.get(Formbuilder.options.mappings.OPTIONS) || []

    newOption = Formbuilder.generateSingleDefaultOption()

    if i > -1
      options.splice(i + 1, 0, newOption)
    else
      options.push newOption

    @model.set Formbuilder.options.mappings.OPTIONS, options
    @model.trigger "change:#{Formbuilder.options.mappings.OPTIONS}"

    # let's focus on the newly added element...
    targetSlot = 1 * (if i > -1 then i + 1 else options.length - 1)
    ($(".edit-response-field .sortableParentContainer .option .option-label-input")[targetSlot]).focus()

    @forceRender()

  removeOption: (e) ->
    $el = $(e.currentTarget)
    index = @$el.find(".js-remove-option").index($el)
    @removeOptionAtIndex(index)

  removeOptionAtIndex: (index) ->
    options = @model.get Formbuilder.options.mappings.OPTIONS
    options.splice index, 1
    @model.set Formbuilder.options.mappings.OPTIONS, options
    @model.trigger "change:#{Formbuilder.options.mappings.OPTIONS}"
    @forceRender()

  defaultUpdated: (e) ->
    $el = $(e.currentTarget)

    unless @model.get(Formbuilder.options.mappings.FIELD_TYPE) == 'checkboxes' # checkboxes can have multiple options selected
      @$el.find(".js-default-updated").not($el).attr('checked', false).trigger('change')

    @forceRender()

  forceRender: ->
    @model.trigger('change')


class BuilderView extends Backbone.View
  SUBVIEWS: []

  events:
    'click .js-undo-delete': 'undoDelete'
    'click .js-save-form': 'saveForm'
    'click .fb-tabs a': 'showTab'
    'click .fb-add-field-types a': 'addField'
    'click .fb-edit-finished a': 'showTabAddField'

  # unless the user is editing text, let's intercept delete keypresses. otherwise too easy to go back in the history
  # similarly for enter - some browsers (safari, IE) submit the form, which we don't want.
  captureDeleteAndEnter: (evt) ->
    if (evt.which == DELETE_KEYCODE or evt.keyCode == DELETE_KEYCODE)
      if (evt.target and (evt.target.type == "text" or evt.target.type == "textarea"))
        return true
      else
        return false
    else if (evt.which == ENTER_KEYCODE or evt.keyCode == ENTER_KEYCODE)
      if (evt.target and (evt.target.type == "textarea"))
        return true
      else
        return false


  initialize: (options) ->
    $(document).keydown(@captureDeleteAndEnter)

    $(document).tooltip({
        track: true
        items: ".fb-add-field-types a"
        show: { delay: 500 }
        content: ( ->
          return Formbuilder.fields[$(this).attr("data-field-type")].instructionDetails
        )
      }
    )


    {selector, @formBuilder, @bootstrapData} = options

    if (!(@bootstrapData instanceof Array))
      @bootstrapData = @bootstrapData.fields

    # This is a terrible idea because it's not scoped to this view.
    if selector?
      @setElement $(selector)

    # Create the collection, and bind the appropriate events
    @collection = new FormbuilderCollection
    @collection.bind 'add', @addOne, @
    @collection.bind 'reset', @reset, @
    @collection.bind 'change', @handleFormUpdate, @
    @collection.bind 'remove add reset', @hideShowNoResponseFields, @
    @collection.bind 'remove', @ensureEditViewScrolled, @

    # Create the undo stack, and bind the appropriate events
    @undoStack = new DeletedFieldCollection
    @undoStack.bind 'add remove', @setUndoButton, @

    @render()
    @collection.reset(@bootstrapData)
    #If this is (a new form OR one without a submit button) and formbuilder is configured to add one
    if _.pathGet(@bootstrapData?[@bootstrapData?.length-1], Formbuilder.options.mappings.FIELD_TYPE) isnt 'submit_button' and
        Formbuilder.options.FORCE_BOTTOM_SUBMIT
      newSubmit = new FormbuilderModel
      setter = {}
      setter[Formbuilder.options.mappings.LABEL]       = 'Submit'
      setter[Formbuilder.options.mappings.FIELD_TYPE]  = 'submit_button'
      newSubmit.set(setter)
      @collection.push(newSubmit)
    @initAutosave()
    @setUndoButton()

  initAutosave: ->
    @formSaved = true
    @saveFormButton = @$el.find(".js-save-form")
    @saveFormButton.attr('disabled', true).text(Formbuilder.options.dict.ALL_CHANGES_SAVED)

    setInterval =>
      @saveForm.call(@)
    , 5000

    if Formbuilder.options.WARN_IF_UNSAVED
      $(window).bind 'beforeunload', =>
        if @formSaved then undefined else Formbuilder.options.dict.UNSAVED_CHANGES

  setUndoButton: ->
    @$undoDeleteButton = @$el.find('.js-undo-delete')
    if not @undoStack.length
      @$undoDeleteButton.attr('disabled', true)
                        .text(Formbuilder.options.dict.NOTHING_TO_UNDO)

      @$undoDeleteButton.css("display", "none")
    else
      topModel = @undoStack.at(@undoStack.length - 1).get('model')
      lastElType = topModel.get(Formbuilder.options.mappings.FIELD_TYPE)
      lastElLabel = topModel.get(Formbuilder.options.mappings.LABEL)
      @$undoDeleteButton.attr('disabled', false)
                        .text(Formbuilder.options.dict.UNDO_DELETE(lastElType, lastElLabel))
      @$undoDeleteButton.css("display", "inline-block")

  reset: ->
    @$responseFields.html('')
    @addAll()

  render: ->
    @$el.html Formbuilder.templates['page']()

    # Save jQuery objects for easy use
    @$fbLeft = @$el.find('.fb-left')
    @$responseFields = @$el.find('.fb-response-fields')

    @bindWindowScrollEvent()
    @hideShowNoResponseFields()

    # Render any subviews (this is an easy way of extending the Formbuilder)
    new subview({parentView: @}).render() for subview in @SUBVIEWS

    return @

  stripPx: (pxVal) ->
    rv = pxVal.substring(0, pxVal.length-2)
    return 1 * rv

  
  bindWindowScrollEvent: ->
    $(window).on 'scroll', =>
      @positionLeftHandUI()

  ###
  vanilla formbuilder just scrolls the left hand ui based on the window scroll position, with a lower and a (rather inaccurate) upper
  bound. This reworked version keeps the ui "pinned" to the top of the screen more or less.
  figures out where the fb-left div should be so that it stays onscreen, follows user interactions, etc. snaps or animates.
  ###
  positionLeftHandUI: (doAnimate = false) ->
    return if @$fbLeft.data('locked') == true

    windowScrollPos = $(document).scrollTop()
    scrollerHeight = @stripPx(@$fbLeft.css("height"))

    fbRight = @$el.find('.fb-right')
    fbRightHeight = @stripPx(fbRight.css("height"))
    fbTopRelativeToDocument = fbRight.offset().top

    minAllowableScroll = 0
    maxAllowableScroll = fbRightHeight - scrollerHeight

    # for brand new or other very small forms, maxAllowable might be zero!
    maxAllowableScroll = Math.max(minAllowableScroll, maxAllowableScroll)

    # add handling for scrolling to an edited item?

    proposedMargin = Math.min(Math.abs(Math.min(minAllowableScroll, fbTopRelativeToDocument - windowScrollPos)), maxAllowableScroll)

    if (doAnimate)
      # console.log "animation b: " + proposedMargin
      @$fbLeft.stop()
      @$fbLeft.animate({
        "margin-top": proposedMargin
      }, 200)
    else
      # console.log "snap b: " + proposedMargin
      @$fbLeft.css
        'margin-top': proposedMargin

  showTabAddField: (e) ->
    @showTabForEl($(".fb-tabs li:eq(0) a"))

  showTabEditField: (e) ->
    @showTabForEl($(".fb-tabs li:eq(1) a"))

  showTab: (e) ->
    $el = $(e.currentTarget)
    @showTabForEl($el)

  showTabForEl: ($el) ->
    # $el = $(e.currentTarget)
    $el.closest('li').addClass('active').siblings('li').removeClass('active')
    target = $el.data('target')
    $(target).addClass('active').siblings('.fb-tab-pane').removeClass('active')

    @unlockLeftWrapper() unless target == '#editField'

    if target == '#editField' && !@editView && (first_model = @collection.models[0])
      @createAndShowEditView(first_model)

  addOne: (responseField, _, options) ->
    view = new ViewFieldView
      model: responseField
      parentView: @

    #####
    # Calculates where to place this new field.
    #
    # Is this the last submit button?
    if responseField.is_last_submit() and Formbuilder.options.FORCE_BOTTOM_SUBMIT
      @$responseFields.parent().append view.render().el

    # Are we replacing a temporarily drag placeholder?
    else if options.$replaceEl?
      options.$replaceEl.replaceWith(view.render().el)

    # Are we adding to the bottom?
    else if !options.position? || options.position == -1
      @$responseFields.append view.render().el

    # Are we adding to the top?
    else if options.position == 0
      @$responseFields.prepend view.render().el

    # Are we adding below an existing field?
    else if ($replacePosition = @$responseFields.find(".fb-field-wrapper").eq(options.position))[0]
      $replacePosition.before view.render().el

    # Catch-all: add to bottom
    else
      @$responseFields.append view.render().el


  setSortable: ->
    @$responseFields.sortable('destroy') if @$responseFields.hasClass('ui-sortable')
    @$responseFields.sortable
      forcePlaceholderSize: true
      axis: 'y'
      containment: @$responseFields.parent().parent()
      placeholder: 'sortable-placeholder'
      handle: '.cover'
      stop: (e, ui) =>
        if ui.item.data('field-type')
          rf = @collection.create Formbuilder.helpers.defaultFieldAttrs(ui.item.data('field-type')), {$replaceEl: ui.item}
          @createAndShowEditView(rf)

        @handleFormUpdate()
        return true
      update: (e, ui) =>
        # ensureEditViewScrolled, unless we're updating from the draggable
        @ensureEditViewScrolled() unless ui.item.data('field-type')

    @setDraggable()

  setDraggable: ->
    $addFieldButtons = @$el.find("[data-field-type]")

    $addFieldButtons.draggable
      connectToSortable: @$responseFields
      cursorAt: { left: @$responseFields.width()/2, top: 20 }
      distance: 15
      helper: "clone"
      start: (e, ui) =>
        draggedElement = $(ui.helper[0])
        draggedElement.css({
          "height": "80px"
          "width": @$responseFields.width()
        })
        
      old_helper: =>
        $helper = $("<div class='response-field-draggable-helper' />")
        $helper.css
          width: @$responseFields.width() # hacky, won't get set without inline style
          height: '80px'

        $helper

  addAll: ->
    @collection.each @addOne, @
    @setSortable()

  hideShowNoResponseFields: ->
    @$el.find(".fb-no-response-fields")[ if \
      ((@collection.length is 1 and #if there's only a mandatory submit button
        Formbuilder.options.FORCE_BOTTOM_SUBMIT and
        @collection.models[0]?.is_last_submit()) or #or if we have no fields
      @collection.length is 0) then 'show' else 'hide']()

  addField: (e) ->
    field_type = $(e.currentTarget).data('field-type')
    @createField Formbuilder.helpers.defaultFieldAttrs(field_type)

  createField: (attrs, options) ->
    rf = @collection.create attrs, options
    @createAndShowEditView(rf)
    @handleFormUpdate()

    if !options or !options.position
      # user clicked one of the buttons in "add field" tab on left side
      rfEl = @$el.find(".fb-field-wrapper").filter( -> $(@).data('cid') == rf.cid )
      destination = rfEl.offset().top - ($(window).height() / 4)
      $.scrollWindowTo destination, 200
    # else, user selected the "duplicate" button. note that if user dragged a field onto right side, this method does not fire!

  # allowRepeatCreation allows us to re-create an edit view that is already active. This is used when we change the field_type of a form element during editing.
  createAndShowEditView: (model, allowRepeatCreation = false) ->
    $responseFieldEl = @$el.find(".fb-field-wrapper").filter( -> $(@).data('cid') == model.cid )
    #Set the editing classes, including fb-field-wrapper outside the list too (ad-hoc for last submit.)
    $responseFieldEl.addClass('editing').parent().parent().find(".fb-field-wrapper").not($responseFieldEl).removeClass('editing')

    if @editView
      if @editView.model.cid is model.cid and not allowRepeatCreation
        @$el.find(".fb-tabs a[data-target=\"#editField\"]").click()
        @scrollLeftWrapper $responseFieldEl, (oldPadding? && oldPadding)
        return

      oldPadding = @$fbLeft.css('padding-top')
      @editView.remove()

    @editView = new EditFieldView
      model: model
      parentView: @

    $newEditEl = @editView.render().$el
    @$el.find(".fb-edit-field-wrapper").html $newEditEl
    @$el.find(".fb-tabs a[data-target=\"#editField\"]").click()
    @scrollLeftWrapper($responseFieldEl)
    return @

  ensureEditViewScrolled: ->
    return unless @editView
    @scrollLeftWrapper $(".fb-field-wrapper.editing")

  # scroll version 1 - scroll to get the item you're editing onscreen
  ###
  scrollLeftWrapper: ($responseFieldEl) ->
    @unlockLeftWrapper()
    return unless $responseFieldEl[0]
    # console.log "scrolling to [" + ($responseFieldEl.offset().top - @$responseFields.offset().top) + "] (" + $responseFieldEl.offset().top + ")/(" + @$responseFields.offset().top + ")..."
    $.scrollWindowTo ($responseFieldEl.offset().top - @$responseFields.offset().top), 200, =>
      @lockLeftWrapper()
  ###

  ###
  # scroll version 2 - the element you're editing will scroll to about 1/4 of the way down the screen
  scrollLeftWrapper: ($responseFieldEl) ->
    @unlockLeftWrapper()
    return unless $responseFieldEl[0]
    # console.log "scrolling to [" + ($responseFieldEl.offset().top - @$responseFields.offset().top) + "] (" + $responseFieldEl.offset().top + ")/(" + @$responseFields.offset().top + ")..."

    destination = $responseFieldEl.offset().top - ($(window).height() / 4)

    # scroll window to some position over some number of milliseconds...
    $.scrollWindowTo destination, 200, =>
      @lockLeftWrapper()
  ###

  # scroll version 3 - don't scroll the window at all; instead move the fbLeft interface. less jarring for the user.
  scrollLeftWrapper: ($responseFieldEl) ->
    @lockLeftWrapper()

    fbRight = @$el.find('.fb-right')
    fbRightHeight = @stripPx(fbRight.css("height"))
    scrollerHeight = @stripPx(@$fbLeft.css("height"))
    maxAllowableScroll = fbRightHeight - scrollerHeight
    destination = Math.min(maxAllowableScroll, $responseFieldEl.offset().top - @$responseFields.offset().top)

    destination = Math.max(destination, 0)

    # console.log "animation a: " + destination
    @$fbLeft.stop()
    @$fbLeft.animate({
      "margin-top": destination
    }, 200)

  lockLeftWrapper: ->
    @$fbLeft.data('locked', true)

  unlockLeftWrapper: ->
    @$fbLeft.data('locked', false)
    @positionLeftHandUI(true)

  handleFormUpdate: ->
    return if @updatingBatch
    @formSaved = false
    @saveFormButton.removeAttr('disabled').text(Formbuilder.options.dict.SAVE_FORM)

  saveForm: (e) ->
    return if @formSaved
    @formSaved = true
    @saveFormButton.attr('disabled', true).text(Formbuilder.options.dict.ALL_CHANGES_SAVED)
    @collection.sort()
    payload = JSON.stringify {fields: @collection.toJSON()}

    if Formbuilder.options.HTTP_ENDPOINT then @doAjaxSave(payload)
    @formBuilder.trigger 'save', payload

  doAjaxSave: (payload) ->
    $.ajax
      url: Formbuilder.options.HTTP_ENDPOINT
      type: Formbuilder.options.HTTP_METHOD
      data: payload
      contentType: "application/json"
      success: (data) =>
        @updatingBatch = true

        for datum in data
          # set the IDs of new response fields, returned from the server
          @collection.get(datum.cid)?.set({id: datum.id})
          @collection.trigger 'sync'

        @updatingBatch = undefined

  deleteToStack: (model) ->
    @undoStack.push({
      position: model.indexInDOM() #this must be called first, before the model is removed
      # model: @collection.clone(model)
      model: model.clone()
      })
    model.destroy()

  undoDelete: (e) ->
    restoree = @undoStack.pop()
    @collection.create(restoree.get('model'), {position: restoree.get('position')})
    return false

class Formbuilder

  @eventTickets:
    events: null

    loadData: () ->
      eventInfo = jQuery("#event_rel_info")
      if (eventInfo.length > 0) 
        Formbuilder.eventTickets.events = []
        eventArray = JSON.parse(eventInfo[0].innerHTML)
        jQuery.each(eventArray, (index, event) ->
          Formbuilder.eventTickets.events[event.id] = event
        )

    getHtmlSelect: () ->
      displayStr = ""
      htmlSelect = ""
      if (Formbuilder.eventTickets.events == null) 
        Formbuilder.eventTickets.loadData()
      jQuery.each(Formbuilder.eventTickets.events, (index, event) ->
        if (event)
          displayStr = event.name + " [" + event.datetime_pretty + "] [ID: " + event.id + "]"
          htmlSelect += '<option value="' + event.id + '">' + displayStr + "</option>\n"
      )
      if (htmlSelect == "")
        displayStr = "No Event Relationships present."
        htmlSelect += '<option value="">' + displayStr + "</option>"
      else 
        htmlSelect = '<option value=""></option>' + htmlSelect
      return htmlSelect

    getEventName: (event_id) ->
      if (Formbuilder.eventTickets.events == null) 
        Formbuilder.eventTickets.loadData()
      event = Formbuilder.eventTickets.events[event_id];
      if (event)
        title = event.name + ", " + event.datetime_pretty;
      return title || ""

  @helpers:
    defaultFieldAttrs: (field_type) ->
      attrs = {}
      _.pathAssign(attrs, Formbuilder.options.mappings.LABEL, '')
      _.pathAssign(attrs, Formbuilder.options.mappings.FIELD_TYPE, field_type)
      _.pathAssign(attrs, Formbuilder.options.mappings.REQUIRED, Formbuilder.options.REQUIRED_DEFAULT)

      Formbuilder.fields[field_type].defaultAttributes?(attrs) || attrs

    simple_format: (x) ->
      x?.replace(/\n/g, '<br />')

    fieldIsEmptyOrNull: (s) ->
      s == null || s == undefined || emptyOrWhitespaceRegex.test(s)

    warnIfEmpty: (s, warning) ->
      if (Formbuilder.helpers.fieldIsEmptyOrNull(s))
        return "<span class='fb-error'><i class='fa fa-exclamation'></i> " + warning + "</span>"
      s

  @getNextUniqueOptionId: ->
    # take two - just use underscore utility to generate a unique id, prefixed with "c" also so that we
    # don't collide with the id's generated for the other form elements. (in the db these will all be getting
    # the prefixes stripped off, so it's important that there are no collisions)
    # return _.uniqueId("c")

    #take three - track it ourselves. Getting weird behavior,probably b/c of mixing across options and top level model elements
    return @getNextUniqueGlobalId("c")

  @getNextUniqueGlobalId: (prefix="") ->
    nextId = ++Formbuilder.maxUsedId
    if prefix != ""
      return prefix + nextId
    else
      return nextId
    
  @options:
    BUTTON_CLASS: 'fb-button'
    HTTP_ENDPOINT: ''
    HTTP_METHOD: 'POST'

    SHOW_SAVE_BUTTON: true
    WARN_IF_UNSAVED: true # this is on navigation away
    FORCE_BOTTOM_SUBMIT: true
    REQUIRED_DEFAULT: true
    ALLOW_TYPE_CHANGE: false

    UNLISTED_FIELDS: [
     'submit_button'
    ]

    mappings:
      SIZE: 'field_options.size'
      UNITS: 'field_options.units'
      LABEL: 'label'
      DEFAULT_VALUE: 'default_value'
      FIELD_TYPE: 'field_type'
      REQUIRED: 'required'
      ADMIN_ONLY: 'admin_only'
      OPTIONS: 'field_options.options'
      DESCRIPTION: 'field_options.description'
      INCLUDE_OTHER: 'field_options.include_other_option'
      INCLUDE_BLANK: 'field_options.include_blank_option'
      INTEGER_ONLY: 'field_options.integer_only'
      MIN: 'field_options.min'
      MAX: 'field_options.max'
      MINLENGTH: 'field_options.minlength'
      MAXLENGTH: 'field_options.maxlength'
      LENGTH_UNITS: 'field_options.min_max_length_units'
      FILE_UPLOAD_EXTENSION_RESTRICTIONS: 'file_upload_extension_restrictions'
      FILE_UPLOAD_TYPE_RESTRICTIONS: 'file_upload_type_restrictions'
      FILE_UPLOAD_SIZE_RESTRICTION: 'file_upload_size_restriction'
      EVENT_TICKETS_EVENT_ID: 'event_tickets_event_id'
      EVENT_TICKETS_NUM_TOTAL_AVAILABLE: 'event_tickets_num_total_available'
      EVENT_TICKETS_MAX_PER_PERSON: 'event_tickets_max_per_person'
      EVENT_TICKETS_EVENT_CLOSE_DATETIME: 'event_tickets_event_close_datetime'

    dict:
      ALL_CHANGES_SAVED: 'All changes saved'
      EMPTY_LABEL_WARNING: 'Enter a label'
      EMPTY_OPTION_WARNING: 'Enter an option'
      EMPTY_OPTION_LIST_WARNING: 'Enter options'
      SAVE_FORM: 'Save form'
      UNSAVED_CHANGES: 'You have unsaved changes. If you leave this page, you will lose those changes!'
      NOTHING_TO_UNDO: 'Nothing to restore'
      UNDO_DELETE: (lastElType, lastElLabel) ->
        'Undo deletion of ' + _(lastElType).capitalize() + " Field '" + _(lastElLabel).truncate(15) + "'"

  @fields: {}
  @inputFields: {}
  @nonInputFields: {}
  debug: {}

  # returns an array of field types that we support
  @getSupportedFields: () ->
    merged = {}
    $.extend(true, merged, @inputFields, @nonInputFields)

    rv = _(merged).map((obj, key) =>
      { type:obj.type, sorter:obj.order, value: key, display: if obj.prettyName then obj.prettyName else key }
      )

    nonInput = "non_input"
    # rv.sort()
    rv.sort (a,b) ->
      if (a.type == nonInput and b.type != nonInput)
        return 1
      else if (a.type != nonInput and b.type == nonInput)
        return -1
      
      if (a.sorter > b.sorter)
        return 1
      else if (a.sorter < b.sorter)
        return -1
      else
        return 0

    # rv = ["checkboxes","text"]
    # rv = @inputFields
    # console.log "look:"
    # console.log rv
    return rv

  @registerField: (name, opts) ->
    for x in ['view', 'edit']
      opts[x] = _.template(opts[x])

    opts.field_type = name

    Formbuilder.fields[name] = opts

    #register field in edit pane
    if name not in Formbuilder.options.UNLISTED_FIELDS # safety net if config is never used
      if opts.type == 'non_input'
        Formbuilder.nonInputFields[name] = opts
      else
        Formbuilder.inputFields[name] = opts

  saveForm: => #expose an instance method to manually save the data
    @mainView.saveForm()

  @config: (options) ->
    Formbuilder.options = $.extend(true, Formbuilder.options, options)

    # Set inputFields and nonInputFields to the non-unlisted fields
    if options.UNLISTED_FIELDS?
      listed_fields = _.omit(Formbuilder.fields, Formbuilder.options.UNLISTED_FIELDS)
      Formbuilder.inputFields = {} #clear lists used by the "Add field" view
      Formbuilder.nonInputFields = {}
      for name, data of listed_fields
        if data.type == 'non_input'
          Formbuilder.nonInputFields[name] = data
        else
          Formbuilder.inputFields[name] = data

  ###
  previously generating a {label:"",checked:false} option was spread over a few locations...each of the radio/dropdown/checkboxes scripts had this logic for creating
  an array of starter data, and the addOption function had it as well. Especially with the addition of the "reasonOptionId" field this was getting out of hand. 
  Not the most elegant fix, but breaking it into this single function and adding a helper method for creating an array of them for the field scripts to hook into.
  ###
  @generateSingleDefaultOption: ->
    return {label:"", checked:false, reasonOptionId:Formbuilder.getNextUniqueOptionId()}

  @generateDefaultOptionsArray: ->
    rv = []
    for i in [0..1]
      rv.push(Formbuilder.generateSingleDefaultOption())
    return rv

  ###
  (take 2: no need to pass around the "maxUsedOptionId" param on the xml. Instead we'll just assign a new guaranteed unique name via
  Underscore's uniqueId method.)

  the individual options that make up a radiobutton/dropdown/checkbox element all need unique id elements.
  Since this is getting bolted onto Formbuilder, this method ensures that any supplied bootstrap data 
  has id's on all elements.

  Note that similar logic exists on the PHP side so much of this is just being overly cautious...although
  it also allows us to stay closer to the main formbuilder codebase with just this shim in the middle.
  ###
  ###
  preprocessBootstrapDataForOptionsValidity: (args) ->
    bootstrapData = args.bootstrapData
    if (bootstrapData instanceof Array)
      fields = bootstrapData
    else
      fields = bootstrapData.fields

    for f,i in fields
      if f.field_options? and f.field_options.options?
        for opt in f.field_options.options
          if (!opt.reasonOptionId?)
            opt.reasonOptionId = Formbuilder.getNextUniqueOptionId()
  ###


  # 2014-12-05 fix START
  ###
  Uncovered a bug where element/option id's were colliding under certain circumstances. This leads to XML that looks ok at first
  glance, and renders ok in Formbuilder, but when Thor/Disco attempt to use it they get quite understandably confused and have
  to choose to only display one of the elements. Spent quite awhile trying to figure out why this is happening, as its hard to
  reproduce. Best guess is that it's related to Underscore's uniqueId function, which we were using both
  in Backbone models and as the source behind the reasonOptionId attribute.

  Hopeful solution - stop using _.uniqueId and instead:
  1. when data first comes into Formbuilder, we take a pass through it. See the performInitialUniqueIdPass function. This
     looks at all elements and options and detects collisions and missing id's, adding those elements to a list to come back
     at on a second pass. Also tracks the maximum id encountered across all elements.

  2. added a new function, Formbuilder::getNextUniqueGlobalId, that is used as a replacement for _.uniqueId, based on the
     maximum id calculated in step 1.

  3. for all the problem elements detected in step 1, we take a pass through those and plug valid id's into the bootstrap json
     using getNextUniqueGlobalId. This is then passed off to the model and we proceed as normal. It's important to note
     that given good bootstrap data, no alterations are made, so if we ever do allow form editing in Reason w/o db 
     destruction, this won't be a problem.

  4. altered Formbuilder.getNextUniqueOptionId to use getNextUniqueGlobalId instead. This takes care of option id's.

  5. updated the FormbuilderModel constructor to use getNextUniqueGlobalId too. This one is a little trickier as a cid
     is actually assigned in the guts of Backbone DeepModel; so when creating a new element, the constructor modification
     will blow those away with unique ones we generate, while if we're dealing with stuff the user saved to the db earlier,
     things are left alone. Again, this is to maintain consistent id's wherever possible.
  ###
  reassignElementIdentifier: (fields, slot) ->
    fields[slot].cid = Formbuilder.getNextUniqueGlobalId("c")

  reassignOptionIdentifier: (fields, elSlot, optSlot) ->
    f = fields[elSlot]
    if f.field_options? and f.field_options.options?
      f.field_options.options[optSlot].reasonOptionId = Formbuilder.getNextUniqueOptionId()

  reassignIdentifiers: (fields) ->
    # console.log "*** REASSIGNING IDENTIFIERS ***"
    for fixer in @elsAndOptsToReId
      fixChunks = fixer.split(":")
      if fixChunks[0] == "element"
        @reassignElementIdentifier(fields, Number(fixChunks[1]))
      else if fixChunks[0] == "option"
        slotChunks = (fixChunks[1]).split(",")
        @reassignOptionIdentifier(fields, Number(slotChunks[0]), Number(slotChunks[1]))

  # returns true if this identifier is previously used
  trackDupes: (identifier) ->
    if (!identifier or identifier == "")
      return false

    # strip out all non-numeric characters.
    if (!Number.isInteger(identifier))
      identifier = Number(identifier.replace(/\D/g, ''))

    if (@dupeIdTracker[identifier])
      @dupeIdTracker[identifier] = @dupeIdTracker[identifier] + 1
    else
      @dupeIdTracker[identifier] = 1

    Formbuilder.maxUsedId = Math.max(Formbuilder.maxUsedId, identifier)

    return @dupeIdTracker[identifier] > 1

  performInitialUniqueIdPass: (args) ->
    console.log "performInitialUniqueIdPass start with draggable fix..."
    # need some variables to track everything
    @madeInitialIdAdjustments = false
    @dupeIdTracker = {}
    Formbuilder.maxUsedId = -1;
    @elsAndOptsToReId = []

    bootstrapData = args.bootstrapData
    if (bootstrapData instanceof Array)
      fields = bootstrapData
    else
      fields = bootstrapData.fields

    for f,i in fields
      if (!f.cid? or @trackDupes(f.cid))
        # console.log "this toplevel is either missing an id or has a dupe id"
        @elsAndOptsToReId.push "element:" + i
        # if all we added an id to was the submit_button, a forced save is NOT necessary
        if f.field_type != "submit_button"
          @madeInitialIdAdjustments = true;

      if f.field_options? and f.field_options.options?
        for opt, k in f.field_options.options
          # console.log "checking opt [" + opt.label + "]"

          if (!opt.reasonOptionId? or @trackDupes(opt.reasonOptionId))
            # console.log "this lption either missing an id, or has a dupe id"
            @elsAndOptsToReId.push "option:" + i + "," + k
            @madeInitialIdAdjustments = true;

    @reassignIdentifiers(fields)
  # 2014-12-05 fix END

    
  constructor: (instanceOpts={}) ->
    _.extend @, Backbone.Events
    args = _.extend instanceOpts, {formBuilder: @}
    # @preprocessBootstrapDataForOptionsValidity(args)
    @performInitialUniqueIdPass(args)
    @mainView = new BuilderView args

    if @madeInitialIdAdjustments
      # we made some changes - force a save!
      @mainView.formSaved = false

    @debug.BuilderView = @mainView

window.Formbuilder = Formbuilder

if module?
  module.exports = Formbuilder
else
  window.Formbuilder = Formbuilder

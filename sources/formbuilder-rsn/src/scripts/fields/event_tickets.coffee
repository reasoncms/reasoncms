localPrettyName = "Event Tickets"

Formbuilder.registerField 'event_tickets',

  order: 60

  type: 'non_input'

  view: """
    <%= Formbuilder.templates['view/event_tickets']({rf: rf}) %>
   """

  edit: """
    <%= Formbuilder.templates['edit/event_tickets']() %>
  """

  addButton: "<span class='symbol'><span class='fa fa-minus'></span></span> " + localPrettyName

  instructionDetails: """
    <div class="instructionText">
        Use this element to link this Reason Form to Reason Events for ticketing or registration purposes. 
    </div>
  """

  prettyName: localPrettyName

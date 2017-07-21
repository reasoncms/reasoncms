localPrettyName = "Date"

Formbuilder.registerField 'date',

  order: 20

  view: """
    <div class='input-line'>
      <span class='month'>
        <input type="text" />
        <label>MM</label>
      </span>

      <span class='above-line'>/</span>

      <span class='day'>
        <input type="text" />
        <label>DD</label>
      </span>

      <span class='above-line'>/</span>

      <span class='year'>
        <input type="text" />
        <label>YYYY</label>
      </span>
    </div>
       <% if (rf.get(Formbuilder.options.mappings.DATE_FIELD_TIME_ENABLED)) { %>
      <div class='input-line'>
      <span class='hours'>
        <input type="text" />
        <label>HH</label>
      </span>

      <span class='above-line'>:</span>

      <span class='minutes'>
        <input type="text" />
        <label>MM</label>
      </span>

      <span class='above-line'>:</span>

      <span class='seconds'>
        <input type="text" />
        <label>SS</label>
      </span>

      <span class='am_pm'>
        <select>
          <option>AM</option>
          <option>PM</option>
        </select>
      </span>
    </div>
    <% } %>
  """

  edit: """
	<label><input type='checkbox' data-rv-checked='model.<%= Formbuilder.options.mappings.DATE_FIELD_TIME_ENABLED %>' />
		Also Show Time Entry
	</label>
	"""

  addButton: """
    <span class="symbol"><span class="fa fa-calendar"></span></span> Date
  """
  
  prettyName: localPrettyName

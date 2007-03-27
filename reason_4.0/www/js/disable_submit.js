/**
 * Resets ths submit state in case of a timeout
 *
 * @param form_name the form to be reset
 */
function reset_submit_state(form_name)
{
  var form = document.forms[form_name];
  var inputs = form.getElementsByTagName("input");

  for (var i=0; i < inputs.length; i++) {
    if (inputs[i].type=="submit") {
      enable(inputs[i]);
    }
    if (inputs[i].type=="hidden" && inputs[i].__replacement)
      inputs[i].parentNode.removeChild(inputs[i]);
  }
}

/**
 * Enables the submit button and removes the replacement
 *
 * @param submit_button the submit button to enable
 */
function enable(submit_button)
{
  if (submit_button.__original) {
    enabled_submit = submit_button.__original.cloneNode(true);
    submit_button.parentNode.replaceChild(enabled_submit, submit_button);
  }
}

/**
 * Disables the submit button in the form
 *
 * @param event the onsubmit event which contains how the form was submitted
 * @param wait_time how long to disable to form (in milliseconds); defaults to 5000
 *  Usage: <form name="login_form" method="method" action="login_form" id="login_form" onsubmit="disable_submit(event, 1000)")>
 */

function disable_submit(event, wait_time)
{
  var submitted_button = event.explicitOriginalTarget.name;
  var form = event.target;
  var wait_time = (wait_time == null) ? 5000 : wait_time;

  var inputs = form.getElementsByTagName("input");
  for (var i=0; i < inputs.length; i++) {
    if (inputs[i].type=="submit") {
      disable(inputs[i], submitted_button);
    }
  }
  var buttons = form.getElementsByTagName("button");
  for (var i=0; i < buttons.length; i++) {
    disable(buttons[i], submitted_button);
  }

  setTimeout("reset_submit_state('" + form.name +"');", wait_time);
}

/**
 * Disable the submit button and creates a replacement
 *
 * @param submit_button the submit button to disable
 * @param submitted-button the name of the button used to submit the form
 */
function disable(submit_button, submitted_button)
{
  if (submit_button.name == submitted_button) {
    button_replacement = document.createElement("input");
    button_replacement.type = "hidden";
    button_replacement.__replacement = true;
    button_replacement.name = submit_button.name;
    button_replacement.value = submit_button.value;
    submit_button.parentNode.appendChild(button_replacement);
  }

  disabled_button = document.createElement("input");
  disabled_button.type = "submit";
  disabled_button.disabled = true;
  disabled_button.value = "Please wait...";

  submit_button.parentNode.replaceChild(disabled_button, submit_button);
  disabled_button.__original = submit_button;
}

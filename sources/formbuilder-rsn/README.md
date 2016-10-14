Formbuilder.js (Reason CMS fork)
============

Formbuilder is a graphical interface for letting users build their own webforms. Think Wufoo or Google Forms, but a lightweight component that can be integrated into your application.

CarletonWebStu/formbuilder, in particular, is concerned with flexibility.
* Touch is supported.
* Fields in the palette may be shown to the user depending on their support in the rest of your system.
* Almost every feature can be turned off with an easy switch.
* An AJAX backend is not assumed. A saveForm() function is exposed to maximize flexibility.
* The form can be given a mandatory yet customizeable submit button at its end.

*Formbuilder.js only handles the client-side logic of creating a form. It will output a structured JSON representation of your form, but saving the form, rendering it on the server, and storing users' responses is all up to you. If you're using Rails, there is also [Formbuilder.rb](https://github.com/dobtco/formbuilder-rb), a Rails engine that's designed to provide this server-side functionality.*

## Demo of this fork
[Click here](http://carletonwebstu.github.io/formbuilder-rsn/) to see Formbuilder in action.

## Fork-specific information

This fork is a close cousin to JSlote/formbuilder. It includes alterations to specific fields that make them compatible with the [Reason CMS](http://apps.carleton.edu/opensource/reason/) web form management system. All Reason CMS-specific development and pull requests should be directed at this fork, but any new features/bugfixes should be directed at JSlote/formbuilder.

## Basic usage
```
<div id='formbuilder'></div>

<script>
var formbuilder = new Formbuilder('#formbuilder');
</script>
```

## Configuring the script

To set the various options of the script at runtime, use `Formbuilder.config()`

```
Formbuilder.config({
  UNLISTED_FIELDS: ['submit_button',
                    'address',
                    'email',
                    'price',
                    'website',
                    'file'],
  SHOW_SAVE_BUTTON: false,
  FORCE_BOTTOM_SUBMIT: false
})
```

A suggested technique is to put the config function in another file and include it after formbuilder.js, like this:

```
<script src="dist/formbuilder.js"></script>
<script src="formbuilder-config.js"></script>
```

See more usage examples in the [wiki](https://github.com/dobtco/formbuilder/wiki).

## Design &amp; Dependencies

Formbuilder itself is a pretty small codebase (6kb gzip'd javascript) but it *does* rely on some external libraries, namely Backbone &amp; Rivets. We use bower to manage our dependencies, which can be seen [here](https://github.com/dobtco/formbuilder/blob/master/bower.json). I'd like to reduce some of these in the future, (especially font-awesome, because that's just silly,) but for now that's what you'll have to include.

Formbuilder consists of a few different components that all live in the `Formbuilder` namespace:

- `Formbuilder.templates` are compiled Underscore.js templates that are used to render the Formbuilder interface. You can see these individual files in the `./templates` directory, but if you're including `formbuilder.js`, you don't need to worry about them.

- `Formbuilder.fields` are the different kinds of inputs that users can add to their forms. We expose a simple API, `Formbuilder.registerField()`, that allows you to add more kinds of inputs.

- `Formbuilder.views`

Because of its modular nature, Formbuilder is easy to customize. Most of the configuration lives in class variables, which means you can simply override a template or method. If you have questions, feel free to open an issue -- we've tried to bridge the gap between convention and configuration, but there's no guarantee that we were successful.

## Data format

Keeping with the customizable nature of Formbuilder, you are also able to modify how Formbuilder structures its JSON output. The [default keypaths](https://github.com/dobtco/formbuilder/blob/master/coffee/main.coffee#L20) are:

```coffeescript
SIZE: 'field_options.size'
UNITS: 'field_options.units'
LABEL: 'label'
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
```

Which outputs JSON that looks something like:

```javascript
[{
    "label": "Please enter your clearance number",
    "field_type": "text",
    "required": true,
    "field_options": {},
    "cid": "c6"
}, {
    "label": "Security personnel #82?",
    "field_type": "radio",
    "required": true,
    "field_options": {
        "options": [{
            "label": "Yes",
            "checked": false
        }, {
            "label": "No",
            "checked": false
        }],
        "include_other_option": true
    },
    "cid": "c10"
}, {
    "label": "Medical history",
    "field_type": "file",
    "required": true,
    "field_options": {},
    "cid": "c14"
}]
```

## Events
More coming soon...

#### `save`
```
var builder = new Formbuilder('#formbuilder');

builder.on('save', function(payload){
  ...
});
```

## Questions?

Have a question about Formbuilder? Feel free to [open a GitHub Issue](https://github.com/dobtco/formbuilder/issues/new) before emailing one of us directly. That way, folks who have the same question can see our communication.

## Developing
Our build system relies on [node and npm](http://nodejs.org/). Install those first.

Next, clone this repository. From inside the `formbuilder/` directory, run these commands:
1. `npm install` - installs all the build system's dependencies
2. `bower install` - installs the runtime dependencies (JS)
3. `grunt watch` - begin reactive compiling
4. open `example/index.html` and you're all set!

It may also make sense to run the following to merge any changes from JSlote/formbuilder to into your local repo:
```
git remote add upstream https://github.com/JSlote/formbuilder.git
git pull upstream gh-pages
```

There is a sublime-project file for Sublime Text 3, if that is your editor of choice.

## License
MIT

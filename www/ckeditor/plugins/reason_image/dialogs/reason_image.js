/**
 * Reason image plugin dialog window definition
 * 
 * See http://docs.ckeditor.com/#!/guide/plugin_sdk_sample_1
 */

CKEDITOR.dialog.add( 'reasonImageDialog', function( editor ) {
	return {

		// Basic properties of the dialog window: title, minimum size.
		title: 'Insert image',
		minWidth: 400,
		minHeight: 400,

		// Dialog window content definition.
		contents: [
		    // Definition of the Basic Settings dialog tab (page).
			{				
				id: 'tab-existing',
				label: 'Exisiting image',
				elements: [
					{
						// Text input field for the abbreviation text.
						type: 'text',
						id: 'abbr',
						label: 'Abbreviation',

						// Validation checking whether the field is not empty.
						validate: CKEDITOR.dialog.validate.notEmpty( "Abbreviation field cannot be empty." )
					},
					{
						// Text input field for the abbreviation title (explanation).
						type: 'text',
						id: 'title',
						label: 'Explanation',
						validate: CKEDITOR.dialog.validate.notEmpty( "Explanation field cannot be empty." )
					}
				]
			},

			// Definition of the Advanced Settings dialog tab (page).
			{
				id: 'tab-web',
				label: 'Image at web address',
				elements: [
					{
						// Another text field for the abbr element id.
						type: 'text',
						id: 'id',
						label: 'Id'
					}
				]
			}
		],

		// This method is invoked once a user clicks the OK button, confirming the dialog.
		onOk: function() {

			// The context of this function is the dialog object itself.
			// http://docs.ckeditor.com/#!/api/CKEDITOR.dialog
			var dialog = this;

			// Create a new <abbr> element.
			var reason_image = editor.document.createElement( 'reason_image' );

			// Set element attribute and text by getting the defined field values.
			reason_image.setAttribute( 'title', dialog.getValueOf( 'tab-existing', 'title' ) );
			reason_image.setText( dialog.getValueOf( 'tab-existing', 'abbr' ) );

			// Now get yet another field value from the Advanced Settings tab.
			var id = dialog.getValueOf( 'tab-web', 'id' );
			if ( id )
				reason_image.setAttribute( 'id', id );

			// Finally, insert the element into the editor at the caret position.
			editor.insertElement( reason_image );
		}
	};
});


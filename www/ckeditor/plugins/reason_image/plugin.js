CKEDITOR.plugins.add( 'reason_image', {
    icons: 'reason_image',
    init: function( editor ) {
    	// Define editor command that opens the reason image dialog window
    	editor.addCommand('reason_image', new CKEDITOR.dialogCommand('reasonImageDialog',{
            allowedContent: 'img[alt,src,style]'
        }));
        editor.ui.addButton( 'reason_image', {
            label: 'Insert Image',
            command: 'reason_image',
            toolbar: 'insert'
        });
        // Register dialog file -- this.path is the plugin folder path
        CKEDITOR.dialog.add('reasonImageDialog', this.path + 'dialogs/reason_image.js');
    },
});
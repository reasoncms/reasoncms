var EnvTools = {
	setUp: function(test) {
		var corral = document.getElementById('loki_corral');
		if (!corral) {
			corral = document.createElement('FORM');
			corral.id = 'loki_corral';
			corral.method = 'post';
			corral.action = '';
			document.body.appendChild(corral);
		}
		
		var context = test.context;
		context.textarea = document.createElement('TEXTAREA');
		context.textarea.rows = 8;
		context.textarea.cols = 80;
		
		context.editor = new UI.Loki;
		corral.appendChild(context.textarea);
		
		Crucible.augment(context, TestTools);
	},
	
	tearDown: function(test) {
		var context = test.context;
		
		if (context.textarea.parentNode)
			context.textarea.parentNode.removeChild(context.textarea);
		if (context.editor.root && context.editor.root.parentNode) {
			context.editor.root.parentNode.removeChild(context.editor.root);
		}

		delete context.editor;
		delete context.textarea;
	}
};

var fixture = Crucible.addFixture('loki', EnvTools);

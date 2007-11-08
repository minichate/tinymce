/**
 * $Id$
 *
 * @author Moxiecode
 * @copyright Copyright � 2004-2007, Moxiecode Systems AB, All rights reserved.
 */

(function() {
	var DOM = tinymce.DOM;

	/**#@+
	 * @class This class is used to create a UI button. A button is basically a link
	 * that is styled to look like a button or icon.
	 * @member tinymce.ui.Button
	 * @base tinymce.ui.Control
	 */
	tinymce.create('tinymce.ui.Button:tinymce.ui.Control', {
		/**
		 * Constructs a new button control instance.
		 *
		 * @param {String} id Control id for the button.
		 * @param {Object} s Optional name/value settings object.
		 */
		Button : function(id, s) {
			this.parent(id, s);
			this.classPrefix = 'mceButton';
		},

		/**#@+
		 * @method
		 */

		/**
		 * Renders the button as a HTML string. This method is much faster than using the DOM and when
		 * creating a whole toolbar with buttons it does make a lot of difference.
		 *
		 * @return {String} HTML for the button control element.
		 */
		renderHTML : function() {
			var s = this.settings;

			if (s.image)
				return '<a id="' + this.id + '" href="javascript:;" class="mceButton mceButtonEnabled ' + s['class'] + '" onmousedown="return false;" title="' + DOM.encode(s.title) + '"><img class="icon" src="' + s.image + '" /></a>';

			return '<a id="' + this.id + '" href="javascript:;" class="mceButton mceButtonEnabled ' + s['class'] + '" onmousedown="return false;" title="' + DOM.encode(s.title) + '"><span class="icon ' + s['class'] + '"></span></a>';
		},

		/**
		 * Post render handler. This function will be called after the UI has been
		 * rendered so that events can be added.
		 */
		postRender : function() {
			var t = this, s = t.settings;

			tinymce.dom.Event.add(t.id, 'click', function(e) {
				if (!t.isDisabled())
					return s.onclick.call(s.scope, e);
			});
		}

		/**#@-*/
	});
})();

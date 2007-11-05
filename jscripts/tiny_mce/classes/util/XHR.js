/**
 * $Id$
 *
 * @author Moxiecode
 * @copyright Copyright � 2004-2006, Moxiecode Systems AB, All rights reserved.
 */

/**
 * This class enables you to send XMLHTTPRequests cross browser.
 */
tinymce.create('static tinymce.util.XHR', {
	/**
	 * Sends a XMLHTTPRequest.
	 * Consult the Wiki for details on what settings this method takes.
	 *
	 * @param {Object} o Object will target URL, callbacks and other info needed to make the request.
	 */
	send : function(o) {
		var x, t, w = window, c = 0;

		// Default settings
		o.scope = o.scope || this;
		o.success_scope = o.success_scope || o.scope;
		o.error_scope = o.error_scope || o.scope;
		o.async = o.async === false ? false : true;
		o.data = o.data || '';

		function get(s) {
			x = 0;

			try {
				x = new ActiveXObject(s);
			} catch (ex) {
			}

			return x;
		};

		x = w.XMLHttpRequest ? new XMLHttpRequest() : get('Msxml2.XMLHTTP') || get('Microsoft.XMLHTTP');

		if (x) {
			if (x.overrideMimeType)
				x.overrideMimeType(o.content_type);

			x.async = o.async;
			x.open(o.type || (o.data ? 'POST' : 'GET'), o.url, o.async);

			if (o.content_type)
				x.setRequestHeader('Content-Type', o.content_type);

			x.send(o.data);

			// Wait for response, onReadyStateChange can not be used since it leaks memory in IE
			t = w.setInterval(function() {
				if (x.readyState == 4 || c++ > 10000) {
					w.clearInterval(t);

					if (o.success && c < 10000 && x.status == 200)
						o.success.call(o.success_scope, '' + x.responseText, x, o);
					else if (o.error)
						o.error.call(o.error_scope, c > 10000 ? 'TIMED_OUT' : 'GENERAL', x, o);

					x = null;
				}
			}, 10);
		}
	}
});

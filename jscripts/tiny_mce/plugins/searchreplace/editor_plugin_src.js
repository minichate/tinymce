/**
 * $Id$
 *
 * @author Moxiecode
 * @copyright Copyright � 2004-2007, Moxiecode Systems AB, All rights reserved.
 */

(function() {
	tinymce.create('tinymce.plugins.SearchReplacePlugin', {
		SearchReplacePlugin : function(ed, url) {
			function open(m) {
				ed.windowManager.open({
					file : url + '/searchreplace.htm',
					width : 400 + ed.getLang('searchreplace.delta_width', 0),
					height : 160 + ed.getLang('searchreplace.delta_height', 0),
					inline : 1
				}, {
					mode : m,
					search_string : ed.selection.getContent({format : 'text'}),
					plugin_url : url
				});
			};

			// Register commands
			ed.addCommand('mceSearch', function() {
				open('search');
			});

			ed.addCommand('mceReplace', function() {
				open('replace');
			});

			// Register buttons
			ed.addButton('search', 'searchreplace.search_desc', 'mceSearch');
			ed.addButton('replace', 'searchreplace.replace_desc', 'mceReplace');

			ed.addShortcut('ctrl+f', 'searchreplace.search_desc', 'mceSearch');
		},

		getInfo : function() {
			return {
				longname : 'Search/Replace',
				author : 'Moxiecode Systems AB',
				authorurl : 'http://tinymce.moxiecode.com',
				infourl : 'http://wiki.moxiecode.com/index.php/TinyMCE:Plugins/searchreplace',
				version : tinymce.majorVersion + "." + tinymce.minorVersion
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('searchreplace', tinymce.plugins.SearchReplacePlugin);
})();
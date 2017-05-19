var Shadowlab = new Vue({
	data: vueData,
	el: "#shadowlab",

	// since the <title> tag lays outside of the div#shadowlab element,
	// we have to alter it after the Vue object is mounted by hand as
	// follows.

	mounted: function() {
		document.title = this.title + " | Shadowlab";
	},

	filters: {
		capitalize: function(str) {

			// source: http://locutus.io/php/strings/ucwords/

			return (str + '').replace(/^([a-z\u00E0-\u00FC])|\s+([a-z\u00E0-\u00FC])/g, function($1) {
				return $1.toUpperCase()
			})
		},

		nl2br: function(str) {

			// source: http://locutus.io/php/strings/nl2br/

			if (typeof str === 'undefined' || str === null) {
				return ''
			}

			// altered source: removed switch between XHTML and HTML
			// version of the break tag.

			return (str + '').replace(/(\r\n|\n\r|\r|\n)/g, '<br>' + '$1')
		},

		makeUpdateLink: function(id) {
			return window.location.href + '/update/' + id;
		}
	}
});

document.addEventListener("DOMContentLoaded", function() {
	new Searchbar();
	new Summarizer();
	new Focuser();
});

var Summarize = Class.extend({

	// this object encapsulates behaviors necessary to "open and close" table rows in
	// table.summarized elements.

	"init": function(element) {
		if (element.tagName == "TABLE") {
			element = $(element);
			if (element.hasClass("summarized")) {

				// now that we know that our element is a table.summarized element, we'll
				// observe clicks on the appropriate links.

				$(".summary th a", element).click(function(event) {
					var element = $(this);
					element.closest("tbody").toggleClass("clicked");

					var table = element.closest("table");
					if (table.children("tbody.clicked").length) {
						table.children("tbody:not(.clicked)").css("background-color", "#C0C0C0");
					} else {
						table.children("tbody").css("background-color", "#FFF");
					}

					event.stopPropagation();
					event.preventDefault();
				});
			}
		}
	}
});

$(document).ready(function() {
	$("table.summarized").each(function() {
		var summarized = new Summarize(this);
	});
});

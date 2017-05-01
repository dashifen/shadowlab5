var Clicker = Class.extend({

	init: function(element) {
		$(element).click(function(event) {
			var target = $(event.target);

			// since the clicker object watches the body for clicks, we have to be sure that
			// we don't do anything with events that we don't care about.  so, only if our
			// target has the class "clicker" and if it tells us what to do when we click it
			// do we continue.

			if (target.hasClass("clicker") && target.is("[data-action]")) {

				// now that we've confirmed that this is a clicker, we'll want to get it's
				// data so we can send it to the server.  the data-action attribute tells us
				// where it should go.

				var data = target.data();
				var action = data.action;

				// some clickers might have an icon within them.  if so, we want to switch it
				// to the spinner.  right now, we always use this on bullets and we always
				// make those bullets the fa-genderless icon because it's nicely sized.

				// TODO: detect the class to which we want to switch away from and back to.

				var icon = target.find(".fa");
				icon.toggleClass("fa-genderless fa-spin fa-spinner");
				$.getJSON("/ajax/private/" + data.action, data).done(function(json) {

					// now that we're back, we'll try to toggle our icon back to the way
					// it was.  we don't much care if this works or not, and jQuery's nice
					// enough to be sure that it doesn't cause an error when there isn't
					// an icon.

					icon.toggleClass("fa-genderless fa-spin fa-spinner");

					// if it was successful, then we want to put an alert on screen with
					// the description fetched from the server.  otherwise, we alert with
					// an error message.

					if (json.success) {
						sweetAlert({
							"title": target.text(),
							"text": json.description,
							"confirmButtonText": "Close",
							"allowOutsideClick": true,
							"animation": false,
							"html": true
						});
					} else {
						sweetAlert({
							"title": "Whoops",
							"text": "This one couldn't be found in the database.  Tell Dash he messed it up.",
							"confirmButtonText": "Close",
							"allowOutsideClick": true,
							"animation": false,
							"html": true
						});
					}
				});
			}
		});
	}
});

$(document).ready(function() {
	$("head").append('<link href="/includes/styles/min/other/clicker.min.css" rel="stylesheet" media="screen">');
	Clicker = new Clicker(document.body);
});
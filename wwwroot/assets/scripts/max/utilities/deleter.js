var Deleter = Class.extend({

	// this object performs adds an are-you-sure behavior to delete actions a less obtrusive way
	// that using the confirm() javascript function to do so.  this is usually applied to individual
	// items with the "deleter" class, but in case there's a bunch of those on one page, you can 
	// also observe a parent element and watch for clicks on such elements.
	
	init: function(element) {
		element = !element ? $(".deleter") : $(element);
		element.click($.proxy(this.is_confirmed, this));
	},
	
	is_confirmed: function(event) {
		// this method ensures that we must click on an elment twice before it is deleted.  the first time sets
		// our confirmation and the latter one actually performs our delete.
		
		var element = $(event.target);
		if(element.hasClass("deleter")) {
			if(element.data("confirmed")) this.do_delete(element);
			else this.do_confirm(element);
			return false;
		}
	},
	
	do_confirm: function(element) {
		
		// confirmation is as simple as adding some confirmation data and changing the display of our icon.
		// before we do our confirmation here, though, we check to see if a confirmation event handler has
		// been registered.  then, based on the results of that event, if there is one, we may skip directly
		// to our delete.
		
		var event = $.Event("deleter:confirmation");
		element.trigger(event);
		
		// if our results are undefined, then there was no handler for the event we triggered.  in this case,
		// we handle our confirmation here.
		
		if (!event.isDefaultPrevented()) {
			element.toggleClass("fa-times fa-question-circle");
			element.attr("data-confirmed", 1);
		} else {
			
			// otherwise, we assume that the handler for our event has performed the confirmation and we 
			// go onto the delete method immeidately, passing it our results.
			
			this.do_delete(element, event.results);
		}
	},
	
	do_delete: function(element, result) {
		
		// on our element there must be data-id and data-action attributes.  these are what tell us what
		// handles our removal on the server-side and what to remove. 
		
		var id = element.attr("data-id");
		var action = element.attr("data-action");
		var data = { "id": id, "ajax": true };
		if(result) data.result = result;
		
		// before we do our AJAX delete, we want to trigger a deleter:before event.  if that event
		// returns false, then we quit.  we pass it all of our data so that it can make an informed
		// decision.
		
		var before = $.Event("deleter:before");
		before.data = data;
		element.trigger(before);
		
		if (!before.isDefaultPrevented()) {
			if(!action) throw "Unable to perform an ajax delete without action";
			
			// if our before event results were undefined (i.e. there were none) or the results 
			// are not Boolean false, then we want to start our delete as follows.
			
			$.post(action, data).done(function(json) {
				if (json.success) {

					// if the removal was a success, then we trigger our after event.  this one is
					// regularly used to do things like remove or hide elements on-screen.

					var after = $.Event("deleter:after");
					after.data = data;
					element.trigger(after);
					
					if (!after.isDefaultPrevented()) {
						
						// if there was no behvaiors for the after event, then we want to see if we
						// can do the default behavior:  remove the table row containing our element.
						// we'll see if this element is in a table row with a set removable flag.
						
						var row = element.closest("tr");
						if (row.data("removable")) {
							row.remove();
						}
					}
					
				} else {
					
					// if we've loaded the Modaal object (http://www.humaan.com/modaal/) then we
					// use it to display our message.  otherwise, we use a simple alert.  a Modaal
					// object is preferred; it can display HTML (like mailto: links).
				
					if(typeof(sweetAlert) === "function") {
						sweetAlert({
							text: json.message,
							title: "Unable to Delete",
							animation: false,
							html: true
						});
					}
						
					else {
						alert(json.message);
					}
				}
			});
		}
	}
});

$(document).ready(function() {
	
	// we like to use the Sweet Alert modal dialog for our Deleter object.  we'll
	// include it's JS and CSS files here if the object isn't already defined.
	
	if (typeof(sweetAlert) !== "function") {
		$("head").append('<link href="/includes/styles/min/third-party/sweetalert.min.css" rel="stylesheet" media="screen">');
		$("body").append('<script src="/includes/scripts/min/third-party/sweetalert.min.js">');
	}

	var default_deleter = new Deleter();
});

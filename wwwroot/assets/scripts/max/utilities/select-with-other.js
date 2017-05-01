var SelectWithOther = Class.extend({

	// this object adds some behaviors to select.with-other elements.  these have
	// an input field on-screen that allows them to enter a new option when their
	// response is not found within the list of options on-screen.  
	
	"init": function() {
		$("select[data-with-other]").change(this.toggle_other_field);
	},
	
	"toggle_other_field": function(event) {

		// when our select element is changed, we grab a reference to it and its
		// "other" field.  then, if the element's value is not "?" we want to be 
		// sure it's hidden.  then, we also toggle some of its properties to help
		// the client-side know what to do with it.

		var element = $(event.target);
		var input = element.siblings("input[data-other]");
		
		input.toggleClass("hidden", element.val() != "?");
		
		if (input.hasClass("hidden")) {
			input.attr("aria-required", "false");
			input.prop("required", false);
		} else {
			input.attr("aria-required", "true");
			input.prop("required", true);
			input.focus();
		}				
	}
});

$(document).ready(function() { SelectWithOther = new SelectWithOther(); });

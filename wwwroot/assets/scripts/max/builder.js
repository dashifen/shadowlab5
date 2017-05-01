var builder = Class.extend({

	// our builder object works with form.builder-details and table.builder-template
	// to help quickly roll a specific type of critter on-screen.

	init: function() {
		this.details  = $(".builder-details");
		this.template = $(".builder-template");

		this.details.change(this.build.bind(this));
		this.details.find("[type=reset]").click(this.reset.bind(this));
	},

	build: function(event) {
		var field = event.target.name;
		var value = $(event.target).val();

		this.template.find("[data-" + field + "]").each(function() {
			var element = $(this);
			var display = value != "reset"
				? element.data(field)[value]
				: element.data("original");

			element.html(display);
		});
	},

	reset: function() {
		this.template.find("[data-original]").each(function() {
			var element = $(this);
			element.html(element.data("original"));
		});
	}



});

$(document).ready(function() {
	builder = new builder();
});
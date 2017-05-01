var Searchbar = Class.extend({
	init: function() {
		// in order to work, we need both a searchbar element and a searchable one.  we'll look for those 
		// elements first and, if we find them, then we can continue.		
		
		this.searchbar  = $("[data-searchbar]");
		this.searchable = $("[data-searchable]");
		if(this.searchbar.length==0 || this.searchable.length==0) return;
		
		this.search = $.proxy(this.search, this);
		this.reset  = $.proxy(this.reset,  this);
		
		// if we're still executing, then we want to observe the field elements within the searchbar for some events.  
		// the specific events change based on what type of fields we encounter but we always call the search method.  
		
		this.fields = this.searchbar.find(":input:not(.no-hook)").each(function(i, element) {
			element = $(element);

			switch (element.prop("type")) {
				case "text": element.keyup(this.search);        break;
				case "select-one": element.change(this.search); break;
				case "checkbox": element.click(this.search);    break;
				case "reset": element.click(this.reset);        break;
			}
		}.bind(this));

		// by setting this flag before we call search and then resetting it after, if our
		// search results in only one visible row, we show the description for it.

		this.show_description = true;
		this.search();
		this.show_description = false;
	},
	
	search: function() {

		// when we're searching, we want to loop over our searchbar and use any values we find within it to limit
		// the displayed table rows.  we use AND logic here -- if we've entered something into a text field and 
		// selected something else, both must match in order to show a table row.  or, if the table row doesn't 
		// match even one of our criteria, then it is hidden.
		
		this.searchbar.trigger("searchbar:before");
		var rows = this.searchable.find("[data-row]");
		if (rows.length == 0) {
			rows = this.searchable.find("tbody tr");
		}
	
		rows.show().each(function(i, element) {
			
			// our rows all have HTML5 data- attributes that we compare against the similarly named properties of
			// our criteria in order to determine whether or not each individual row should be shown or hidden.
			
			var show = true;
			var row = $(element);
			
			this.fields.each(function() {
				var field = $(this);
				var type = field.context.type;
				if(type == "reset") return;
				
				var value = type=="checkbox" ? field.context.checked : field.val();
				if (!value || value.length == 0 || value == "all") return;
				
				// if we haven't left our anonymous function at this time, then we want to compare our value against
				// the row's data with the same name as the field's ID.  if this is a text field, we use a regular 
				// expression to match.  otherwise, we check for equality.  notice that we remove the following 
					
				var name = field.attr("id").replace(/_(?:search|filter|toggle)$/, "");

				if (type != "text") {
					// if there's a name + "-list" data setting, then we want to use the is() method to determine
					// if our value is a part of that list.  otherwise, we'll simply look for a match to our value.
					
					if(row.data(name + "-list")) show = show && row.is("[data-" + name + "~=" + value + "]");
					else show = show && row.data(name) == value;
				} else {
					// this try block helps to avoid bad regular expressions based on wacky user entry.  if we 
					// throw an exception within, we just skip this criterion.
					
					try { 
						var pattern = new RegExp(value, "i");
						show = show && row.data(name).match(pattern);
					} catch(e) { }
				}
			});
			
			if(!show) row.hide();
		}.bind(this));
		
		this.searchbar.trigger("searchbar:after");

		if (this.show_description ) {

			// if the show_description flag is set and if there's only one visible row,
			// then we set it to be clicked so that it's description gets shown.  this is
			// really only intended to be used during the page load; see the bottom of
			// the init() method for when the flag is set and reset.

			var visible = rows.find(":visible");
			if (visible.length == 1) {
				visible.addClass("clicked");
			}
		}
	},
	
	reset: function() {
		this.searchbar.trigger("searchbar:reset");
		this.searchbar.closest("form").get(0).reset();
		this.searchable.find("tbody tr").show();
		return false;
	}
});


$(document).ready(function() { Searchbar = new Searchbar(); });
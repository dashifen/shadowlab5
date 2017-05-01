var TableSort = Class.extend({
	
	// given a table, this object will prepare the means by which we can sort based on specified table
	// headings.  
	
	init: function(table, column, direction) {
		this.direction = !direction ? "asc" : direction;
		this.column = !column ? 0 : column;
		
		// first thing to do is prepare our table.  we grab all the sortable columns and then jam an <em> element
		// into their text.  we also find the one identified by column and apply the direction class to it.  
		// finally, we observe our sorting columns for clicks which fire the on_column_click method below.
		
		this.table = $(table);
		this.columns = this.table.find("th[scope=col]");
		this.columns.each(function(i, col) {
			
			// notice that this anonymous function is bound to the TableSort object using the Function.bind() 
			// method below.  therefore, this refers to that object and not to a column.  that's what the col
			// variable is for.
			
			col = $(col);
			if(col.is(".no-sort")) return;
			
			// if we didn't return above, then we do want to provide the means by which to sort the table
			// based on this column.  if the index of our loop (i) matches the column that we're starting our
			// sort on, we add the directional class to it.  then, we wrap the text in the column's heading
			// with an <em> tag which is styled to provide a visual clue to the sortability of this column.
			
			if (i==this.column) {
				col.addClass(this.direction);
			}
			
			col.html("<em>" + col.text() + "</em>");
			
			// and finally, we bind clicks on our column to the on_column_click method below.  notice we
			// bind that to the TableSort object, too.
			
			col.click(this.on_column_click.bind(this));
		}.bind(this));
	},
	
	on_column_click: function(event) {
		var col = (event.target.tagName != "TH") ? $(event.target).parent("th") : $(event.target);
		var i = col.index();
		
		// if the clicked column does not have the "asc" class then we want to add that class as our new sort
		// direction on that column.  otherwise, if it does have that class, we want to add "desc".  before
		// we remove anything from the current column, we check to see if it has the opposite class and, if so,
		// we know we can simply reverse our sort rather than re-sorting.  then, we remove the appropriate 
		// class from this column and both of them from its siblings.
		
		var newdir = !col.hasClass("asc") ? "asc" : "desc";
		var reversal = col.hasClass(newdir=="asc" ? "desc" : "asc");
		col.removeClass(newdir=="asc" ? "desc" : "asc").addClass(newdir);
		col.siblings().removeClass("asc").removeClass("desc");
		
		// now, we actually need to do our sort.  first, we need to identify some details relating to this
		// column and our rows, and then we can sort using the custom ordering function below.
		
		var tbody = this.table.find("tbody");
		var rows = tbody.find("tr").toArray();
		if(rows.length > 1) {
			
			// if we have more than one row, we can sort.  otherwise, it doesn't matter what we want to do, one 
			// row is always in order with itself!  but, assuming we have work to do, we first check to see if 
			// this is a simple reversal and, if so, we can use the reverse() method which is faster than our
			// sort.  		
			
			var ordered = reversal ? rows.reverse() : rows.sort(function(a, b) {
				
				// to sort the rows, we first grab the cells withi both the a and b rows which match the selector 
				// we identified above.  then, if that cell has a data-sort attribute, we use it, otherwise we sort
				// based on the cell's text.
				
				var x = $(a).find("*:eq(" + i + ")");
				var y = $(b).find("*:eq(" + i + ")");
				x = x.is("[data-sort]") ? x.attr("data-sort") : x.text();
				y = y.is("[data-sort]") ? y.attr("data-sort") : y.text();
				return (x <= y) ? (newdir=="asc" ? -1 :  1) : (newdir=="asc" ?  1 : -1);
			});
			
			// and, now that we have a newly ordered list, we want to remove all rows from the DOM and then re-insert
			// our them in the order we've specified above.
			
			tbody.html("");
			for(var i=0; i < ordered.length; i++) tbody.append(ordered[i]);
			if(this.after && typeof(this.after)=="function") this.after();
		}
	}
});

$(document).ready(function() {
	
	// this object has its own styles.  to avoid having to remember to include them in a page
	// by hand, we'll just add them here with JavsScript
	
	$("head").append('<link href="/includes/styles/min/other/table-sort.min.css" rel="stylesheet" media="screen">');
	
	$("table[data-sortable]").each(function() {
		var table = $(this);
	
		// we can specify a column and default direction for our sort using data- attributes.  if we 
		// don't find them, then we default to an ascending sort in the first column.
		
		var column = table.is("[data-column]") ? table.data("column") : 0
		var direction = table.is("[data-dir]") ? table.data("dir") : "asc";
		new TableSort(table, column, direction);
	});
});



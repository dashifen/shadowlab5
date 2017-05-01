(function($) {
	var Globals = Class.extend({
		
		// the global object simply adds some behaviors which should apply throughout the
		// site.  scripts that apply to other parts of the site are separated into their own
		// JS files that are included on-demand on a per-page basis.
		
		"init": function() {
			this.add_mailtos();
			this.righthand_menu_fix();
			
			// if there's an element on-screen with a focus class, we'll get the first one
			// and focus it.  there shouldn't really ever be more than one, but we'll be 
			// sure to get the first here.
			
			var focus = $(".focus");
			if (focus.length) {
				focus.get(0).focus();
			}

			var resizeTimer = false;
			$(window).on("resize", function() {
				if(resizeTimer) clearTimeout(resizeTimer);
				resizeTimer = setTimeout(function() {
					$(".mainmenu .menu > li").removeClass("menu-overflow no-menu-overflow");
				}, 250);
			});
		},
		
		"add_mailtos": function() {
			$(".email").each(function() {
				var element = $(this);
				element.find("span").remove();
				var email = element.text();
				
				var a = $("<a>");
				a.prop("href", "mailto:" + email);
				a.text(email);
				
				element.html(a);
			});
		},

		"righthand_menu_fix": function() {
			$(".mainmenu .menu > li").on('mouseenter mouseleave', function () {
				var menu = $(this);
				if (!(menu.hasClass("menu-overflow") || menu.hasClass("no-menu-overflow")) && $("ul", this).length) {
					var body = $("body");
					var submenu = $("ul:first", this);
					var submenu_right = submenu.offset().left + submenu.width();
					var body_right = body.offset().left + body.width();
					submenu_right > body_right ? menu.addClass("menu-overflow") : menu.addClass("no-menu-overflow");
				}
			});
		}
	});
	
	$(document).ready(function() { Globals = new Globals(); });


})(jQuery);
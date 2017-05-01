var Dialog = Class.extend({
    "element": null,                   // the element in the DOM that triggers the dialog
    "type": null,                      // the type of the dialog that we're showing

    // this object uses the SweetAlert modal dialog as a foundation on which to build
    // our own dialog system.  these dialogs essentially create an alert, a confirm, and
    // a prompt that the site can use to have a styled version of those JavaScript
    // baseline tools.

    "init": function(element) {
        if (element) {
            var temp = $(element);
            if (temp.data("type")) {
                var type = temp.data("type");
                if (type == "alert" || type == "confirm" || type == "prompt") {
                    this.element = temp;
                    this.type = type;
                }
            }
        }

        // as long as our test above resulted in an "appropriate" element for us to use,
        // then we'll attach a click event to it.  we could use a method instead of an
        // anonymous function, but it's a pretty straightforward so we'll just cram it
        // in here.

        if (this.element) {
            this.element.click(function(event) {
                event.preventDefault();
                event.stopPropagation();

                // this function is bound to the Dialog object.  therefore, we have
                // access to it's properties and methods.  ordinarily, jQuery would
                // have placed us in the scope of the element clicked, but by explicitly
                // binding this anonymous function we can avoid that.

                switch(this.type) {
                    case "alert":   this.do_alert();   break;
                    case "confirm": this.do_confirm(); break;
                    case "prompt":  this.do_prompt();  break;
                }
            }.bind(this));
        }
    },

    "do_alert": function() {
        var href = this.get_href();
        if (href) {
            var title = this.element.data("title");
            if (!title) {
                title = "Note:";
            }

            // to show our alert dialog, we simply get the content from the server for
            // the specified href and then show it using the SweetAlert dialog.

            $.get(href).done(function(content) {
                if (content.length) {
                    sweetAlert({
                        "title": title,
                        "text": content,
                        animation: false,
                        html: true
                    });
                }
            });
        }
    },












    "get_href": function() {

        // we check for a data-href value.  if we don't find that, then we check to see
        // if this element actually has an href attribute.  then, we return the href that
        // we found or false if we didn't find one.

        var href = this.element.data("href");
        if (!href) {
            href = this.element.attr("href");
        }

        return href.length ? href : false;
    }
});

$(document).ready(function() {

    // if the SweetAlert tools haven't been included by hand or by another JavaScript
    // file, we'll want to include them here.  this ensures that we have them available
    // to us when we utilize our own tools defined above.

    if (typeof(sweetAlert) !== "function") {
        $("head").append('<link href="/includes/styles/min/third-party/sweetalert.min.css" rel="stylesheet" media="screen">');
        $("body").append('<script src="/includes/scripts/min/third-party/sweetalert.min.js">');
    }

    // now, we'll look for any items with a class of dialog and having a data-type attribute.
    // when we find them, we'll create a Dialog that uses those to share content with the
    // visitor.

    $(".dialog[data-type]").each(function() { new Dialog(this); });

});

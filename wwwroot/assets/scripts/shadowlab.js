var shadowlab = new Vue({
	data: vueData,

	el: "#shadowlab",

	mounted: function() {
		document.title = this.title;
	}
});

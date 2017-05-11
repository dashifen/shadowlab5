var gulp = require("gulp"),
	sourcemaps   = require("gulp-sourcemaps"),
	autoprefixer = require("gulp-autoprefixer"),
	plumber = require("gulp-plumber"),
	concat  = require("gulp-concat"),
	uglify  = require("gulp-uglify"),
	rename  = require("gulp-rename"),
	sass    = require("gulp-sass"),
	add_src = require("gulp-add-src"),
	cssmin  = require("gulp-cssmin"),
	runseq  = require("run-sequence"),

	files = {
		framework: {
			css: [
				"./node_modules/normalize.css/normalize.css",
				"./node_modules/html5-boilerplate/dist/css/main.css"
			]
		},
		css: {
			src:  "./wwwroot/assets/styles/max",
			dest: "./wwwroot/assets/styles",
			main: "./wwwroot/assets/styles/max/styles.scss",
			sass: "./wwwroot/assets/styles/max/**/*.scss"
		},

		js: {
			dest: "./wwwroot/assets/scripts/",
			js:   [
				"./vendor/dashifen/searchbar/web/scripts/searchbar.js",
				"./wwwroot/assets/scripts/shadowlab.js"
			]
		}
	};

gulp.task("build", function() {
	runseq("framework-setup", "build-css", "build-js");
});

gulp.task("framework-setup", function() {
	return gulp.src(files.framework.css)
		.pipe(rename({ extname: ".scss", prefix: "_" }))
		.pipe(gulp.dest(files.css.src));
});

gulp.task("build-css", function() {
	return gulp.src(files.css.main)
		.pipe(plumber())
		.pipe(sourcemaps.init())
		.pipe(sass({ outputStyle: "compressed" }).on("error", sass.logError))
		.pipe(autoprefixer({ browsers: ["last 2 versions"], cascade: false }))
		.pipe(concat("shadowlab.css"))
		.pipe(rename({ suffix: ".min" }))
		.pipe(sourcemaps.write())
		.pipe(gulp.dest(files.css.dest));
});

gulp.task("build-js", function() {
	gulp.src(files.js.js)
		.pipe(plumber())
		.pipe(sourcemaps.init())
		.pipe(concat("shadowlab.js"))
		.pipe(uglify())
		.pipe(sourcemaps.write())
		.pipe(rename({ suffix: ".min" }))
		.pipe(gulp.dest(files.js.dest));
});

gulp.task("watch", function() {
	gulp.watch([files.css.sass], ["build-css"]);
	//gulp.watch([files.js.js], ["build-js"]);
});

gulp.task("default", ["watch"]);

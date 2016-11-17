var gulp = require('gulp');

gulp.task('default', function() {	 
	console.log('Use the following commands');
	console.log('--------------------------');
	console.log('gulp js				to compile the lsx-currencies.js to lsx-currencies.min.js');
	console.log('gulp compile-js		to compile both JS files above');
	console.log('gulp watch				to continue watching all files for changes, and build when changed');
	console.log('gulp wordpress-lang	to compile the lsx-currencies.pot, en_EN.po and en_EN.mo');
	console.log('gulp reload-node-js	Copy over the .js files from teh various node modules');
});

var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var sort = require('gulp-sort');
var wppot = require('gulp-wp-pot');
var gettext = require('gulp-gettext');
var rename = require('gulp-rename');

gulp.task('js', function () {
	gulp.src('assets/js/lsx-currencies.js')
		.pipe(concat('lsx-currencies.min.js'))
		.pipe(uglify())
		.pipe(gulp.dest('assets/js'));
});
gulp.task('compile-js', (['js']));

gulp.task('watch', function() {
	gulp.watch('assets/js/lsx-currencies.js', ['js']);
});

gulp.task('wordpress-pot', function () {
	return gulp.src('**/*.php')
		.pipe(sort())
		.pipe(wppot({
			domain: 'lsx-currencies',
			destFile: 'lsx-currencies.pot',
			package: 'lsx-currencies',
			bugReport: 'https://bitbucket.org/feedmycode/lsx-currencies/issues',
			team: 'LightSpeed <webmaster@lsdev.biz>'
		}))
		.pipe(gulp.dest('languages'));
});

gulp.task('wordpress-po', function () {
	return gulp.src('**/*.php')
		.pipe(sort())
		.pipe(wppot({
			domain: 'lsx-currencies',
			destFile: 'en_EN.po',
			package: 'lsx-currencies',
			bugReport: 'https://bitbucket.org/feedmycode/lsx-currencies/issues',
			team: 'LightSpeed <webmaster@lsdev.biz>'
		}))
		.pipe(gulp.dest('languages'));
});

gulp.task('wordpress-po-mo', ['wordpress-po'], function() {
	return gulp.src('languages/en_EN.po')
		.pipe(gettext())
		.pipe(gulp.dest('languages'));
});

gulp.task('wordpress-lang', (['wordpress-pot', 'wordpress-po-mo']));

gulp.task('reload-node-js', function() {
	gulp.src('node_modules/flag-icon-css/accounting.js').pipe(gulp.dest('assets/js').on('error', function (err) {console.log('Error!', err);}));
	gulp.src('node_modules/money/money.js').pipe(gulp.dest('assets/js').on('error', function (err) {console.log('Error!', err);}));

	gulp.src('node_modules/js-cookie/src/js.cookie.js')
		.pipe(rename('cookie.js'))
		.pipe(gulp.dest('assets/js')
			.on('error', function (err) {console.log('Error!', err);})
		);
});
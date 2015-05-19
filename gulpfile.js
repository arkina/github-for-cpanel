var gulp = require( 'gulp' );
var tar = require( 'gulp-tar' );
var gzip = require( 'gulp-gzip' );

gulp.task( 'build', function () {
	return gulp.src( [
		'./*',
		'./inc/*',
		'!./node_modules',
		'!./vendor',
		'!./build',
		'!./composer.lock',
		'!./composer.phar',
		'!./package.json',
		'!./gulpfile.js'
	], {base:'./'} )
		.pipe( tar( 'ghcp-release.tar' ) )
		.pipe( gzip() )
		.pipe( gulp.dest( 'build' ) );
} );
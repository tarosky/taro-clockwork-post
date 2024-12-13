const gulp = require( 'gulp' );
const fs = require( 'fs' );
const $ = require( 'gulp-load-plugins' )();
const webpack = require( 'webpack-stream' );
const webpackBundle = require( 'webpack' );
const named = require( 'vinyl-named' );
const mergeStream = require( 'merge-stream' );

let plumber = true;

// Sassのタスク
gulp.task( 'sass', function () {

	return gulp.src( [ './assets/scss/**/*.scss' ] )
		.pipe( $.plumber( {
			errorHandler: $.notify.onError( '<%= error.message %>' )
		} ) )
		.pipe( $.sassGlob() )
		.pipe( $.sourcemaps.init() )
		.pipe( $.sass( require( 'sass' ) )( {
			errLogToConsole: true,
			outputStyle: 'compressed',
			sourceComments: false,
			sourcemap: true,
			includePaths: [
				'./assets/scss',
			]
		} ) )
		.pipe( $.autoprefixer() )
		.pipe( $.sourcemaps.write( './map' ) )
		.pipe( gulp.dest( './dist/css' ) );
} );

// Style lint.
gulp.task( 'stylelint', function () {
	let task = gulp.src( [ './assets/scss/**/*.scss' ] );
	if ( plumber ) {
		task = task.pipe( $.plumber() );
	}
	return task.pipe( $.stylelint( {
		reporters: [
			{
				formatter: 'string',
				console: true,
			},
		],
	} ) );
} );

// Package jsx.
gulp.task( 'jsx', function () {
	return gulp.src( [
		'./assets/js/**/*.js',
	] )
		.pipe( $.plumber( {
			errorHandler: $.notify.onError( '<%= error.message %>' )
		} ) )
		.pipe( named( (file) =>  {
			return file.relative.replace(/\.[^\.]+$/, '');
		} ) )
		.pipe( webpack( require( './webpack.config.js' ), webpackBundle ) )
		.pipe( gulp.dest( './dist/js' ) );
} );

// ESLint
gulp.task( 'eslint', function () {
	let task = gulp.src( [
		'./assets/js/**/*.js',
	] );
	if ( plumber ) {
		task = task.pipe( $.plumber() );
	}
	return task.pipe( $.eslint( { useEslintrc: true } ) )
		.pipe( $.eslint.format() )
		.pipe( $.eslint.failAfterError() );
} );

// Copy bundles.
gulp.task( 'copy', ( done ) => {
	done();
	// return mergeStream(
	// 	gulp.src( [] )
	// 		.pipe( gulp.dest( 'dist/vendor' ) )
	// );
} );

// watch
gulp.task( 'watch', ( done ) => {
	// Make SASS
	gulp.watch( 'assets/scss/**/*.scss', gulp.parallel( 'sass', 'stylelint' ) );
	// Bundle JS
	gulp.watch( [ 'assets/js/**/*.{js,jsx}' ], gulp.parallel( 'jsx', 'eslint' ) );
	done();
} );



// Toggle plumber.
gulp.task( 'noplumber', ( done ) => {
	plumber = false;
	done();
} );

// Build
gulp.task( 'build', gulp.parallel( 'jsx', 'sass', 'copy' ) );

// Default Tasks
gulp.task( 'default', gulp.series( 'watch' ) );

// Lint
gulp.task( 'lint', gulp.series( 'noplumber', gulp.parallel( 'stylelint', 'eslint' ) ) );

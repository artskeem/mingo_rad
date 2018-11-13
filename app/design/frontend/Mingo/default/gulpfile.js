/**
 * Dependencies
 * -----------------------------------------------------------------------------
 */
const gulp = require('gulp');
const gulpLoadPlugins = require('gulp-load-plugins');
const $ = gulpLoadPlugins();
const del = require('del');
const runSequence = require('run-sequence');
const pkg = require('./package.json');
const dirs = pkg['app-config'].directories;

/**
 * Optimize images
 * -----------------------------------------------------------------------------
 */
gulp.task('images', () =>
    gulp.src(`${dirs.dist.images}/**/*`)
        .pipe($.cache($.imagemin({
            progressive: true,
            interlaced: true
        })))
        .pipe(gulp.dest(dirs.dist.images))
        .pipe($.size({title: 'images'}))
);

/**
 * Build styles with pre-processors and post-processors
 * -----------------------------------------------------------------------------
 */
gulp.task('styles', () =>
    gulp.src(`${dirs.src.styles}/**/*.scss`)
        .pipe($.sourcemaps.init())
        .pipe($.sass({
            includePaths: [
                'node_modules/bootstrap/scss',
                'node_modules/animate.css'
            ]
        }).on('error', $.sass.logError))
        .pipe($.autoprefixer())
        .pipe(gulp.dest(dirs.dist.styles))
        .pipe($.cssnano({zindex: false}))
        .pipe($.size({title: 'styles'}))
        .pipe($.rename({ suffix: '.min' }))
        .pipe($.sourcemaps.write())
        .pipe(gulp.dest(dirs.dist.styles))
);

/**
 * Build JavaScript
 * -----------------------------------------------------------------------------
 */
gulp.task('scripts', () =>
    gulp.src(`${dirs.src.scripts}/**/*.js`)
        .pipe(gulp.dest(dirs.dist.scripts))
        .pipe($.size({title: 'styles'}))
        .pipe($.rename({ suffix: '.min' }))
        .pipe(gulp.dest(dirs.dist.scripts))
);

/**
 * Watch for changes
 * -----------------------------------------------------------------------------
 */
gulp.task('watch', () => {
    gulp.watch(`${dirs.src.styles}/**/*.scss`, ['styles']);
});

/**
 * Clean processed files
 * -----------------------------------------------------------------------------
 */
gulp.task('clean', () =>
    del([
        `${dirs.dist.styles}/*.css`,
        `${dirs.dist.scripts}/*.js`
    ])
);

/**
 * Default task
 * -----------------------------------------------------------------------------
 */
gulp.task('default', ['clean'], cb =>
    runSequence(
        'styles',
        'scripts',
        cb
    )
);

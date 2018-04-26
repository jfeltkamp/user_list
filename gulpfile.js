'use strict';
var gulp         = require('gulp');
var browserSync  = require('browser-sync').create();
var sass         = require('gulp-sass');
var cleanCSS     = require('gulp-clean-css');
var autoprefixer = require('gulp-autoprefixer');

// Static Server + watching scss/html files
gulp.task('serve', ['sass'], function() {

  browserSync.init({
    proxy: "monokid8.docker.amazee.io"
  });

  gulp.watch("./sass/*.scss", ['sass']);
  gulp.watch("./css/*.css").on('change', browserSync.reload);
});

// Compile sass into CSS & auto-inject into browsers
gulp.task('sass', function() {
  return gulp.src("./sass/*.scss")
    .pipe(sass())
    .pipe(autoprefixer({
      browsers: ['last 2 versions'],
      cascade: false
    }))
    .pipe(cleanCSS())
    .pipe(gulp.dest("./css"))
    .pipe(browserSync.stream());
});

gulp.task('default', ['serve']);

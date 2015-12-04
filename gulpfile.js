var gulp       = require('gulp');
var Compiler   = require('./resources/gulp/Compiler.js');



// Compiler options
var options = {
    logs:         true,
    storage:      'storage/assets',
    publish:      'public/assets',
    paths:        [
        'resources/stylesheets',
        'resources/javascripts'
    ],
    commonJsBase: [
        'javascripts/app',
        'javascripts/src'
    ]
};


gulp.task('scripts', function () {
    new Compiler(Compiler.EXT_SCRIPT, options)

        // Dependencies
        .js([
            require.resolve('babel-core/browser-polyfill'),
            require.resolve('commonjs-require/commonjs-require'),
            require.resolve('knockout/build/output/knockout-latest')
        ])

        // Application
        .babel([
            'resources/javascripts/src/**/*.js',
            'resources/javascripts/app/**/*.js'
        ], {
            optional: [
                'es7.decorators',
                'es7.classProperties',
                'es7.objectRestSpread',
                'es7.functionBind',
                'es7.trailingFunctionCommas'
            ],
            loose:    [
                'es6.classes'
            ]
        })

        .build('app.js', function (output) {
            console.log('Scripts was be published at:', output);
        });
});


gulp.task('styles', function () {
    new Compiler(Compiler.EXT_STYLE, options)
        .sass([
            'resources/stylesheets/layout.scss'
        ])
        .build('app.css', function (output) {
            console.log('Styles was be published at:', output);
        });
});

gulp.task('watch', function() {
    gulp.watch('resources/javascripts/**/*.js', ['scripts']);
    gulp.watch('resources/stylesheets/**/*.scss', ['styles']);
});

gulp.task('default', ['scripts', 'styles'], function () {

});

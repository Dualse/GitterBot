var gulp    = require('gulp');
var console = require('gulp-util');
var argv    = require('yargs').argv;
var debug   = require('gulp-debug');

/*
 |--------------------------------------------------------------------------
 |                              Всякие утилиты
 |--------------------------------------------------------------------------
 */

var utils = {
    concat:     require('gulp-concat'),
    sourcemaps: require('gulp-sourcemaps'),
    commonjs:   require('gulp-wrap-commonjs'),
    prefixes:   require('gulp-autoprefixer')
};

/*
 |--------------------------------------------------------------------------
 |        Минификаторы для стилей, скриптов, картинок и мб ещё чего
 |--------------------------------------------------------------------------
 */

var minify = {
    css:  require('gulp-minify-css'),
    js:   require('gulp-uglify'),
    gzip: require('gulp-gzip')
};

/*
 |--------------------------------------------------------------------------
 |                            Сам компилятор
 |--------------------------------------------------------------------------
 */

var Compiler = (function () {
    // Окружения
    Compiler.ENV_LOCAL      = 'local';
    Compiler.ENV_PRODUCTION = 'production';

    // Типы данных
    Compiler.EXT_STYLE  = 'css';
    Compiler.EXT_SCRIPT = 'js';

    // Версия
    Compiler.VERSION    = 1.1;

    /**
     * @param format Тип данных
     * @param options Окружение
     * @constructor
     */
    function Compiler(format, options) {
        if (this instanceof Function) {
            return new this(format, options);
        }

        this.options = {
            // Environment
            env: options.env || (
                argv.production ? Compiler.ENV_PRODUCTION : Compiler.ENV_LOCAL
            ),

            // Storage path
            storage: (options.storage || 'storage') + '/' + this.hash() + '/',

            // Public path
            publish: options.publish || 'out',

            // Default namespace (CommonJS default path)
            base: options.commonJsBase || 'app',

            // Show logs
            logs: (options.logs == null ? true : options.logs),

            // File paths
            paths: options.paths || []
        };

        this.format       = format || Compiler.EXT_SCRIPT;
        this.streams      = [];
        this.files        = [];
        this.prependFiles = [];
    }

    /**
     * Prepend files in result (shims as example)
     *
     * @param file
     * @returns {Compiler}
     */
    Compiler.prototype.prepend = function (file) {
        for (var i = 0; i < this.prependFiles; i++) {
            if (this.prependFiles[i] === file) {
                return this;
            }
        }

        this.prependFiles.push(file);

        return this;
    };

    /**
     * Simple function of random hash
     *
     * @returns {string}
     */
    Compiler.prototype.hash = function () {
        return Math.floor((1 + Math.random()) * 0x10)
            .toString(16);
    };

    /**
     * Добавить файл (с компилем)
     *
     * @param files Файл
     * @param compiler Коллбек, принимающий стрим, должен возвращать стрим
     * @param wrap
     * @returns {Compiler}
     */
    Compiler.prototype.add = function (files, compiler, wrap) {
        var self = this;

        if (typeof wrap === 'undefined' || wrap == null) {
            wrap = true;
        }

        if (typeof files === 'undefined' || files == null) {
            throw new Error('Files not exists');
        }

        if (!(files instanceof Array)) {
            files = [files];
        }


        // Appends base paths
        var newFileList = files.slice();
        for (var i = 0; i < this.options.paths.length; i++) {
            var path = this.options.paths[i];

            for (var j = 0; j < files.length; j++) {
                newFileList.push(path + '/' + files[j]);
            }
        }
        files = newFileList;


        var name = 'id' + (this.streams.length + 1) + '_' +
            this.hash() + this.hash() + '.' + this.format;
        this.files.push(this.options.storage + name);

        var stream = gulp.src(files);
        if (self.options.logs) {
            stream = stream.pipe(debug({title: 'add:'}));
        }
        stream = stream
            .on('error', function (error) { console.log('Build error:', error.trace); })
            .pipe(utils.sourcemaps.init());

        // Inject compiler here
        if (compiler != null) {
            stream = compiler(stream);
        }

        // Inject CommonJS for scripts
        if (wrap && this.format === Compiler.EXT_SCRIPT) {
            stream = stream
                .pipe(utils.commonjs({
                    pathModifier: function (path) {
                        if (!(self.options.base instanceof Array)) {
                            self.options.base = [self.options.base];
                        }

                        path = path.replace(/\\/g, '/');

                        self.options.base.forEach(function(query) {
                            path = path.replace(new RegExp('.*?\/' + query + '\/', 'g'), '');
                        });

                        path = path.replace(/\.js|\.es6|\.jsx$/, '');

                        return path;
                    }
                }));
        }

        // Inject autoprefixer
        if (this.format === Compiler.EXT_STYLE) {
            stream = stream
                .pipe(utils.prefixes({
                    browsers: ['last 2 versions'],
                    cascade:  false
                }));
        }

        stream = stream
            .pipe(utils.concat(name))
            .pipe(utils.sourcemaps.write());

        this.streams.push(stream);

        return this;
    };

    /**
     * Сборка всего в одно
     *
     * @param outputName
     * @param callback
     * @returns {Compiler}
     */
    Compiler.prototype.build = function (outputName, callback) {
        var self    = this;
        var streams = this.streams.length;
        var current = 0;

        if (typeof callback === 'undefined' || !(callback instanceof Function)) {
            callback = function (output) {
                console.log('File was be published at:', output);
            };
        }

        // On all streams was finish
        var finish = function () {
            var stream = gulp
                .src(self.prependFiles.concat(self.files), {
                    strict:     true,
                    allowEmpty: false
                });
            if (self.options.logs) {
                stream = stream.pipe(debug({title: 'build:'}));
            }
            stream = stream
                .pipe(debug({title: 'build:'}))
                .on('error', function (error) {
                    console.log('Error when merge result:', error.trace);
                })
                .pipe(utils.sourcemaps.init({loadMaps: true}))
                .pipe(utils.concat(outputName));

            // Minify
            if (self.options.env === Compiler.ENV_PRODUCTION) {
                if (self.format === Compiler.EXT_SCRIPT) {
                    stream = stream.pipe(minify.js({mangle: true}));

                } else if (self.format === Compiler.EXT_STYLE) {
                    stream = stream.pipe(minify.css());
                }
            }

            stream = stream.pipe(utils.sourcemaps.write('./'));

            // Gzip
            if (self.options.env === Compiler.ENV_PRODUCTION) {
                stream = stream
                    .pipe(gulp.dest(self.options.publish))
                    .pipe(minify.gzip());
            }

            stream = stream
                .pipe(gulp.dest(self.options.publish))
                .on('finish', function () {
                    if (callback != null) {
                        callback(self.options.publish + '/' + outputName);
                    }
                });

            return stream;
        };

        /**
         * Merge stream builds
         */
        this.streams.forEach(function (stream, index) {
            stream
                .pipe(gulp.dest(self.options.storage))
                .on('finish', function () {
                    current++;
                    if (streams === current) {
                        return finish();
                    }
                });
        });


        return this;
    };


    /*
     |--------------------------------------------------------------------------
     |                                  COMPILERS
     |--------------------------------------------------------------------------
     */


    /**
     * @param files
     * @param commonJsWrap
     * @returns {*}
     */
    Compiler.prototype.js = function (files, commonJsWrap) {
        return this.add(files, function (stream) {
            return stream;
        }, commonJsWrap || false);
    };

    /**
     * @param files
     * @returns {*}
     */
    Compiler.prototype.css = function (files) {
        return this.add(files, function (stream) {
            return stream;
        }, false);
    };

    /**
     * @param files
     * @param commonJsWrap
     * @returns {*}
     */
    Compiler.prototype.coffee = function (files, commonJsWrap) {
        return this.add(files, function (stream) {
            var coffee = require('gulp-coffee');

            return stream.pipe(
                coffee({bare: true}).on('error', function (error) {
                    console.log('Coffee Error:', error.trace);
                })
            );
        }, commonJsWrap || false);
    };

    /**
     * @param files
     * @param options
     * @returns {Compiler}
     */
    Compiler.prototype.babel = function (files, options) {
        options = {
            modules:   (options.modules     || 'common'),
            optional:  (options.optional    || []),
            blacklist: (options.blacklist   || []),
            plugins:   (options.plugins     || []),
            loose:     (options.loose       || [])
        };

        if (this.options.env === Compiler.ENV_PRODUCTION) {
            options.optional.push('minification.removeConsole');
            options.optional.push('minification.removeDebugger');
        }

        return this.add(files, function (stream) {
            var babel = require('gulp-babel');

            return stream.pipe(
                babel(options).on('error', function (error) {
                    console.log('Babel Error:', error.trace, '[opt]: ', options);
                })
            );
        }, options.modules === 'common');
    };

    /**
     * @param files
     * @returns {*}
     */
    Compiler.prototype.sass = function (files) {
        return this.add(files, function (stream) {
            var sass = require('gulp-sass');

            return stream.pipe(
                sass().on('error', function (error) {
                    console.log('Sass Error:', error.trace);
                })
            );
        }, false);
    };

    /**
     * @param files
     * @returns {*}
     */
    Compiler.prototype.less = function (files) {
        return this.add(files, function (stream) {
            var less = require('gulp-less');

            return stream.pipe(
                less({}).on('error', function (error) {
                    console.log('Less Error:', error.trace);
                })
            );
        }, false);
    };

    return Compiler;
})();

// Export
module.exports = Compiler;

module.exports = function( grunt ) {

	var pkg = grunt.file.readJSON( 'package.json' );

	console.log( pkg.title + ' - ' + pkg.version );

	// Files to include/exclude in a release.
	var distFiles = [
		'**',
		'!.git/**',
		'!.tx/**',
		'!assets/css/sass/**',
		'!assets/images/wp/**',
		'!build/**',
		'!node_modules/**',
		'!.bowercc',
		'!.editorconfig',
		'!.gitignore',
		'!.gitmodules',
		'!.jscsrc',
		'!.jshintrc',
		'!apigen.neon',
		'!bower.json',
		'!composer.json',
		'!composer.lock',
		'!readme.md',
		'!gruntfile.js',
		'!package.json',
		'!**/*~'
	];

	grunt.initConfig( {

		pkg: pkg,

		banner: '/*! <%= pkg.title %> - <%= pkg.version %>\n' +
		' * <%=pkg.homepage %>\n' +
		' * Copyright (c) Moonstone Media <%= grunt.template.today("yyyy") %>\n' +
		' * Licensed GPLv2+' +
		' */\n',

		jshint: {
			options: {
				ignores : [
					'**/*.min.js'
				],
				reporter: require( 'reporter-plus/jshint' )
			},
			all    : [
				'assets/js/*.js',
				'gruntfile.js'
			]
		},

		jscs: {
			all: [
				'assets/js/*.js',
				'!assets/js/*.min.js'
			]
		},

		uglify: {
			all: {
				files  : {
					'assets/js/admin.min.js'             : ['assets/js/admin.js'],
					'assets/js/admin-add-calendar.min.js': ['assets/js/admin-add-calendar.js'],
					'assets/js/default-calendar.min.js'  : ['assets/js/default-calendar.js']
				},
				options: {
					mangle   : {
						except: ['jQuery']
					},
					sourceMap: false,
					preserveComments: false
				}
			}
		},

		sass: {
			options: {
				precision: 2,
				sourceMap: false
			},
			all: {
				files: [{
					expand: true,
					cwd   : 'assets/css/sass/',
					src   : ['*.scss'],
					dest  : 'assets/css/',
					ext   : '.css'
				}]
			}
		},

		postcss: {
			options: {
				processors: [
					require('autoprefixer')({browsers: 'last 2 versions'})
				]
			},
			dist   : {
				expand: true,
				cwd   : 'assets/css',
				src   : [
					'*.css',
					'!*.min.css'
				],
				dest  : 'assets/css'
			}
		},

		cssmin: {
			options: {
				processImport      : false,
				keepSpecialComments: 0
			},
			minify : {
				expand: true,
				cwd   : 'assets/css',
				src   : [
					'*.css',
					'!*.min.css'
				],
				dest  : 'assets/css',
				ext   : '.min.css'
			}
		},

		usebanner: {
			options: {
				position : 'top',
				banner   : '<%= banner %>',
				linebreak: true
			},
			js     : {
				files: {
					src: ['assets/js/*.min.js']
				}
			},
			css    : {
				files: {
					src: ['assets/css/*.min.css']
				}
			}
		},

		watch:  {
			livereload: {
				files  : [
					'assets/css/*.min.css'
				],
				options: {
					livereload: true
				}
			},
			styles: {
				files  : [
					'assets/css/sass/**/*.scss'
				],
				tasks  : ['sass', 'postcss', 'cssmin', 'usebanner:css'],
				options: {
					debounceDelay: 500
				}
			},
			scripts: {
				files  : [
					'assets/js/**/*.js',
					'!assets/js/vendor/**/*.js',
					'!assets/js/**/*.min.js'
				],
				tasks  : ['uglify', 'usebanner:js'],
				options: {
					debounceDelay: 500
				}
			}
		},

		checktextdomain: {
			options: {
				text_domain: 'google-calendar-events',
				keywords: [
					'__:1,2d',
					'_e:1,2d',
					'_x:1,2c,3d',
					'esc_html__:1,2d',
					'esc_html_e:1,2d',
					'esc_html_x:1,2c,3d',
					'esc_attr__:1,2d',
					'esc_attr_e:1,2d',
					'esc_attr_x:1,2c,3d',
					'_ex:1,2c,3d',
					'_n:1,2,4d',
					'_nx:1,2,4c,5d',
					'_n_noop:1,2,3d',
					'_nx_noop:1,2,3c,4d'
				]
			},
			files: {
				src: [
					'includes/**/*.php',
					'google-calendar-events.php',
					'uninstall.php'
				],
				expand: true
			}
		},

		makepot: {
			target: {
				options: {
					cwd            : '',
					domainPath     : '/languages',
					potFilename    : 'google-calendar-events.pot',
					mainFile       : 'google-calendar-events.php',
					include        : [],
					exclude        : [
						'apigen/',
						'assets/',
						'bower_components/',
						'build/',
						'docs/',
						'google-calendar-events/',
						'languages/',
						'libraries/',
						'node_modules',
						'svn',
						'tests',
						'tmp',
						'vendor'
					],
					potComments    : '',
					potHeaders     : {
						poedit                 : true,
						'x-poedit-keywordslist': true,
						'language'             : 'en_US',
						'report-msgid-bugs-to' : 'https://github.com/moonstonemedia/Simple-Calendar/issues',
						'last-translator'      : 'Phil Derksen <pderksen@gmail.com>',
						'language-Team'        : 'Phil Derksen <pderksen@gmail.com>'
					},
					type           : 'wp-plugin',
					updateTimestamp: true,
					updatePoFiles  : true,
					processPot     : null
				}
			}
		},

		po2mo: {
			options: {
				deleteSrc: true
			},
			files  : {
				src   : 'languages/*.po',
				expand: true
			}
		},

		clean: {
			build: [ 'build' ],
			docs: [ 'docs' ]
		},

		copy: {
			main: {
				expand: true,
				src   : distFiles,
				dest  : 'build/google-calendar-events'
			}
		},

		compress: {
			main: {
				options: {
					mode   : 'zip',
					archive: './build/simple-calendar-<%= pkg.version %>.zip'
				},
				expand : true,
				src    : distFiles,
				dest   : '/google-calendar-events'
			}
		},

		wp_deploy: {
			deploy: {
				options: {
					plugin_slug     : 'google-calendar-events',
					plugin_main_file: 'google-calendar-events.php',
					build_dir       : 'build/simple-calendar',
					max_buffer      : 400 * 1024
				}
			}
		},

		shell: {
			options: {
				stdout: true,
				stderr: true
			},
			apigen : {
				command: 'apigen generate'
			},
			txpull : {
				command: 'tx pull -a -f --minimum-perc=1'
			},
			txpush : {
				command: 'tx push -s'
			}
		},

		phpunit: {
			classes: {
				dir: 'tests/phpunit/unit-tests'
			},
			options: {
				bin          : 'vendor/bin/phpunit',
				configuration: 'phpunit.xml',
				testSuffix   : '.php'
			}
		},

		qunit: {
			all: ['tests/qunit/**/*.html']
		}

	} );

	require( 'load-grunt-tasks' )(grunt);

	grunt.loadNpmTasks( 'grunt-composer' );

	grunt.registerTask( 'css',      ['sass', 'postcss', 'cssmin', 'usebanner:css' ] );
	grunt.registerTask( 'js',       ['uglify', 'usebanner:js'] );
	grunt.registerTask( 'default',  ['css', 'jshint', 'jscs', 'js'] );
	grunt.registerTask( 'pot',      ['checktextdomain', 'makepot', 'shell:txpush'] );
	grunt.registerTask( 'localize', ['shell:txpull', 'po2mo'] );
	grunt.registerTask( 'build',    ['composer:install:no-dev', 'composer:dump-autoload:optimize:no-dev', 'clean:build',
	                                 'default', 'checktextdomain', 'copy', 'compress', 'composer:update', 'composer:dump-autoload:optimize'] );

	// TODO Add deploy task
	//grunt.registerTask( 'deploy',   ['test', 'localize', 'default', 'build', 'docs', 'wp_deploy'] );

	// Possible future grunt tasks
	//grunt.registerTask( 'test',     ['phpunit', 'qunit'] );
	//grunt.registerTask( 'docs',     ['clean:docs', 'shell:apigen'] );

	grunt.util.linefeed = '\n';
};

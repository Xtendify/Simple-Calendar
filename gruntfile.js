module.exports = function (grunt) {
	var pkg = grunt.file.readJSON('package.json');

	console.log(pkg.title + ' - ' + pkg.version);

	// Files to include/exclude in a release.
	var distFiles = [
		'assets/**',
		'!assets/css/sass/**',
		'!assets/images/wp/**',
		'google-calendar-events.php',
		'i18n/**',
		'includes/**',
		'license.txt',
		'readme.txt',
		'third-party/**',
		'uninstall.php',
	];

	grunt.initConfig({
		pkg: pkg,

		// Set folder variables.
		dirs: {
			css: 'assets/css',
		},

		// Create comment banner to add to the top of minified .js and .css files.
		banner:
			'/*! <%= pkg.title %> - <%= pkg.version %>\n' +
			' * <%=pkg.homepage %>\n' +
			' * Copyright (c) Xtendify Technologies <%= grunt.template.today("yyyy") %>\n' +
			' * Licensed GPLv2+' +
			' */\n',

		// Validate i18n text domain slug throughout.
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
					'_nx_noop:1,2,3c,4d',
				],
			},
			files: {
				src: ['includes/**/*.php', 'google-calendar-events.php', 'uninstall.php'],
				expand: true,
			},
		},

		// Wipe out build folder.
		clean: {
			build: ['build'],
		},

		// Build the plugin zip file and place in build folder.
		compress: {
			main: {
				options: {
					mode: 'zip',
					archive: './build/simple-calendar-<%= pkg.version %>.zip',
				},
				expand: true,
				src: distFiles,
				dest: '/google-calendar-events',
			},
		},

		// 'css' & 'js' tasks need to copy vendor-minified assets from bower folder to assets folder (select2, etc).
		// 'main' task is for distributing build files.
		copy: {
			css: {
				expand: true,
				cwd: 'bower_components/',
				flatten: true,
				src: ['select2/dist/css/select2.css', 'select2/dist/css/select2.min.css'],
				dest: '<%= dirs.css %>/vendor/',
			},
			main: {
				expand: true,
				src: distFiles,
				dest: 'build/google-calendar-events',
			},
		},

		// Minify .css files.
		cssmin: {
			options: {
				processImport: false,
				keepSpecialComments: 0,
			},
			minify: {
				expand: true,
				cwd: '<%= dirs.css %>',
				src: ['*.css', '!*.min.css'],
				dest: '<%= dirs.css %>',
				ext: '.min.css',
			},
		},

		// Compile all .scss files.
		sass: {
			options: {
				precision: 2,
				sourceMap: false,
			},
			all: {
				files: [
					{
						expand: true,
						cwd: '<%= dirs.css %>/sass/',
						src: ['*.scss'],
						dest: '<%= dirs.css %>/',
						ext: '.css',
					},
				],
			},
		},

		// Add comment banner to each minified .js and .css file.
		usebanner: {
			options: {
				position: 'top',
				banner: '<%= banner %>',
				linebreak: true,
			},
			css: {
				files: {
					src: ['<%= dirs.css %>/*.min.css'],
				},
			},
		},

		// .scss to .css file watcher. Run when project is loaded in PhpStorm or other IDE.
		watch: {
			css: {
				files: '**/*.scss',
				tasks: ['sass'],
			},
		},
	});

	require('load-grunt-tasks')(grunt);

	grunt.registerTask('css', ['copy:css', 'cssmin', 'usebanner:css']);
	grunt.registerTask('js', []);
	grunt.registerTask('default', ['css', 'js']);
	grunt.registerTask('build', ['default', 'checktextdomain', 'clean:build', 'copy:main', 'compress']);

	// TODO Add deploy task
	//grunt.registerTask( 'deploy',	['build'] );

	grunt.util.linefeed = '\n';
};

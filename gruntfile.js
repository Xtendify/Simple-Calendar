module.exports = function (grunt) {
	var pkg = grunt.file.readJSON('package.json');

	console.log(pkg.title + ' - ' + pkg.version);

	// Files to include/exclude in a release.
	var distFiles = [
		'assets/generated/**',
		'assets/images/**',
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
				cwd: 'build/google-calendar-events',
				src: '**',
				dest: '/google-calendar-events',
			},
		},

		// 'main' task is for distributing build files.
		copy: {
			main: {
				expand: true,
				src: distFiles,
				dest: 'build/google-calendar-events',
			},
		},
	});

	require('load-grunt-tasks')(grunt);

	grunt.registerTask('build', ['checktextdomain', 'clean:build', 'copy:main']);

	grunt.util.linefeed = '\n';
};

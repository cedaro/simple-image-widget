/*global exports:false, module:false, require:false */

module.exports = function( grunt ) {
	'use strict';

	require('matchdep').filterDev('grunt-*').forEach( grunt.loadNpmTasks );

	grunt.initConfig({

		jshint: {
			options: {
				jshintrc: '.jshintrc'
			},
			plugin: [
				'Gruntfile.js',
				'assets/js/*.js',
				'!assets/js/*.min.js',
			]
		},

		makepot: {
			plugin: {
				options: {
					mainFile: 'simple-image-widget.php',
					potHeaders: {
						'report-msgid-bugs-to': 'http://wordpress.org/support/plugin/simple-image-widget',
						'language': 'en',
						'plural-forms': 'nplurals=2; plural=(n != 1);',
						'x-poedit-basepath': '../',
						'x-poedit-bookmarks': '',
						'x-poedit-country': 'United States',
						'x-poedit-keywordslist': '__;_e;__ngettext:1,2;_n:1,2;__ngettext_noop:1,2;_n_noop:1,2;_c;_nc:1,2;_x:1,2c;_ex:1,2c;_nx:4c,1,2;_nx_noop:4c,1,2;',
						'x-poedit-searchpath-0': '.',
						'x-poedit-sourcecharset': 'utf-8',
						'x-textdomain-support': 'yes'
					},
					type: 'wp-plugin',
					updateTimestamp: false
				}
			}
		},

		watch: {
			js: {
				files: ['<%= jshint.plugin %>'],
				tasks: ['jshint']
			}
		}

	});

	grunt.registerTask('default', ['jshint', 'watch']);

	/**
	 * Generate documentation using ApiGen.
	 *
	 * @link http://apigen.org/
	 */
	grunt.registerTask('apigen', function() {
		var done = this.async();

		grunt.util.spawn({
			cmd: 'apigen',
			args: [
				'--source=.',
				'--destination=docs',
				'--exclude=*/.git*,*/docs/*,*/node_modules/*,*/tests/*',
				'--title=Simple Image Widget Documentation',
				'--main=SimpleImageWdiget',
				'--report=docs/_report.xml'
			],
			opts: { stdio: 'inherit' }
		}, done);
	});

	/**
	 * PHP Code Sniffer using WordPress Coding Standards.
	 *
	 * @link https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards
	 */
	grunt.registerTask('phpcs', function() {
		var done = this.async();

		grunt.util.spawn({
			cmd: 'phpcs',
			args: [
				'-p',
				'-s',
				'--standard=WordPress',
				'--extensions=php',
				'--ignore=*/node_modules/*,*/tests/*',
				'--report-file=codesniffs.txt',
				'.'
			],
			opts: { stdio: 'inherit' }
		}, done);
	});

};

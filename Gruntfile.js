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

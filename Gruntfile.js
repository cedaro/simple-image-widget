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
				'assets/scripts/*.js',
				'!assets/scripts/*.min.js',
			]
		},

		makepot: {
			plugin: {
				options: {
					mainFile: 'simple-image-widget.php',
					type: 'wp-plugin'
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

};

module.exports = function( grunt, options ) {
	return {
		'default': [
			'build'
		],
		'build': [
			'check',
			'build:css',
			'build:js'
		],
		'build:css': [],
		'build:js': [],
		'check': [
			'jshint'
		],
		'package': [
			'check',
			'replace',
			'build:css',
			'build:js',
			'compress:package'
		]
	};
};

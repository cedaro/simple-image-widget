module.exports = {
	options: {
		jshintrc: 'config/.jshintrc'
	},
	check: [
		'assets/js/{,**/}*.js',
		'!assets/js/*.{bundle,min}.js',
		'!assets/js/vendor/*.js'
	],
	grunt: {
		options: {
			jshintrc: 'config/.jshintnoderc'
		},
		src: [
			'Gruntfile.js',
			'config/grunt/*.js'
		]
	}
};

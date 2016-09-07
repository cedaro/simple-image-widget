var path = require( 'path' );

module.exports = function( grunt ) {
	'use strict';

	if ( ! grunt.option( 'production' ) ) {
		require( 'time-grunt' )( grunt );
	}

	require( 'load-grunt-config' )( grunt, {
		configPath: path.join( process.cwd(), 'config/grunt' ),
		data: {},
		jitGrunt: {
			staticMappings: {
				replace: 'grunt-text-replace'
			}
		}
	});

};

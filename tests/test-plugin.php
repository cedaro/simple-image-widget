<?php

/**
 *
 */
class Test_Simple_Image_Widget_Plugin extends WP_UnitTestCase {

	/**
	 *
	 */
	public function test_image_size_names() {
		$sizes = Simple_Image_Widget_Plugin::get_image_size_names();
		$this->assertEquals( 4, count( $sizes ) );
		$this->assertEquals( 'Thumbnail', $sizes['thumbnail'] );
	}

}

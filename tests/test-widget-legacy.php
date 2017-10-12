<?php

/**
 *
 */
class Test_Simple_Image_Widget_Legacy extends WP_UnitTestCase {

	/**
	 * List of media thumbnail ids
	 * @type array
	 */
	protected $ids = array();

	/**
	 *
	 */
	public function setUp() {
		global $simple_image_widget;

		add_filter( 'is_simple_image_widget_legacy', '__return_true' );

		parent::setUp();
		$simple_image_widget->register_widget();

		require_once( SIW_DIR_TESTDATA . '/../includes/class-simple-image-widget-test.php' );
		register_widget( 'Simple_Image_Widget_Test' );

		$compat = new Simple_Image_Widget_Legacy();
		$compat->load();
	}

	/**
	 * Tear down the test fixture.
	 */
	public function tearDown() {
		foreach ( $this->ids as $id ) {
			wp_delete_attachment( $id, true );
		}

		$uploads = wp_upload_dir();
		foreach ( scandir( $uploads['basedir'] ) as $file ) {
			$this->rmdir( $uploads['basedir'] . '/' . $file );
		}

		parent::tearDown();
	}

		/**
	 *
	 */
	public function test_widget_output() {
		$filename = SIW_DIR_TESTDATA . '/images/blazer-six.png';
		$contents = file_get_contents( $filename );
		$upload = wp_upload_bits( basename( $filename ), null, $contents );
		$id = $this->make_attachment( $upload );

		$expected = <<<HTML
<aside class="widget widget_simpleimage"><img src="{{image_url}}" alt=""></aside>
HTML;

		$upload = wp_upload_dir();
		$expected = str_replace( '{{image_url}}', wp_get_attachment_url( $id ), $expected );

		$args = array(
			'before_widget' => '<aside class="widget %s">',
			'after_widget'  => '</aside>',
			'before_title'  => '<h1 class="widget-title">',
			'after_title'   => '</h1>',
		);

		$instance = array(
			'image'      => wp_get_attachment_url( $id ),
			'text'       => '',
			'title'      => '',
		);

		ob_start();
		the_widget( 'Simple_Image_Widget', $instance, $args );
		$content = ob_get_clean();

		$this->assertEquals( $expected, $content );
	}

	/**
	 *
	 */
	public function test_widget_args_and_instance() {
		global $wp_widget_factory;

		$filename = SIW_DIR_TESTDATA . '/images/blazer-six.png';
		$contents = file_get_contents( $filename );
		$bits = wp_upload_bits( basename( $filename ), null, $contents );
		$id = $this->make_attachment( $bits );

		$args = array(
			'before_widget' => '<aside id="%1$s" class="widget %2$s">',
			'after_widget'  => '</aside>',
			'before_title'  => '<h1 class="widget-title">',
			'after_title'   => '</h1>',
		);

		$instance = array(
			'alt'        => 'Alt Text',
			'image'      => wp_get_attachment_url( $id ),
			'image_size' => 'thumbnail',
			'link'       => 'http://example.com/',
			'link_text'  => 'Link Text',
			'new_window' => false,
			'title'      => 'Title',
			'text'       => 'Text',
		);

		ob_start();
		$widget = $wp_widget_factory->widgets['Simple_Image_Widget_Test'];
		$widget->_set( -1 );
		$widget->widget( $args, $instance );
		ob_end_clean();

		$upload = wp_upload_dir();
		$this->assertEquals( $upload['url'] . '/blazer-six.png', $widget->test_instance['image'] );
		$this->assertEquals( 'Alt Text', $widget->test_instance['alt'] );
	}

	/**
	 *
	 */
	public function test_legacy_fields() {
		global $wp_widget_factory;

		$widget = $wp_widget_factory->widgets['Simple_Image_Widget'];

		$fields = (array) apply_filters( 'simple_image_widget_fields', $widget->form_fields(), $widget->id_base );
		$this->assertEquals( 'legacy', $fields[0] );
		$this->assertFalse( array_search( 'image_size', $fields ) );
	}

	/**
	 * Function snagged from ./tests/post/attachments.php
	 */
	protected function make_attachment($upload, $parent_post_id = 0) {
		$type = '';
		if ( !empty($upload['type']) ) {
			$type = $upload['type'];
		} else {
			$mime = wp_check_filetype( $upload['file'] );
			if ($mime)
				$type = $mime['type'];
		}

		$attachment = array(
			'post_title' => basename( $upload['file'] ),
			'post_content' => '',
			'post_type' => 'attachment',
			'post_parent' => $parent_post_id,
			'post_mime_type' => $type,
			'guid' => $upload[ 'url' ],
		);

		// Save the data
		$id = wp_insert_attachment( $attachment, $upload[ 'file' ], $parent_post_id );
		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
		return $this->ids[] = $id;
	}

}

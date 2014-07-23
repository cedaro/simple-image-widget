<?php

/**
 *
 */
class Test_Simple_Image_Widget extends WP_UnitTestCase {

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

		parent::setUp();
		$simple_image_widget->register_widget();

		require_once( SIW_DIR_TESTDATA . '/../includes/class-simple-image-widget-test.php' );
		register_widget( 'Simple_Image_Widget_Test' );
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
	public function test_widget_registered() {
		global $wp_widget_factory;
		$this->assertTrue( isset( $wp_widget_factory->widgets['Simple_Image_Widget'] ) );
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
<aside id="%1\$s" class="widget %2\$s">

	<p class="simple-image">
		{{image}}	</p>


</aside>
HTML;

		$expected = str_replace( '{{image}}', wp_get_attachment_image( $id, 'thumbnail' ), $expected );

		$args = array(
			'before_widget' => '<aside id="%1$s" class="widget %2$s">',
			'after_widget'  => '</aside>',
			'before_title'  => '<h1 class="widget-title">',
			'after_title'   => '</h1>',
		);

		$instance = array(
			'image_id'   => $id,
			'image_size' => 'thumbnail',
			'link'       => '',
			'link_text'  => '',
			'new_window' => '',
			'title'      => '',
			'text'       => '',
		);

		ob_start();
		the_widget( 'Simple_Image_Widget', $instance, $args );
		$content = ob_get_clean();

		$this->assertEquals( $expected, $content );
	}

	public function test_widget_output_without_image() {
		$expected = <<<HTML
<aside id="%1\$s" class="widget %2\$s">



</aside>
HTML;

		$args = array(
			'before_widget' => '<aside id="%1$s" class="widget %2$s">',
			'after_widget'  => '</aside>',
			'before_title'  => '<h1 class="widget-title">',
			'after_title'   => '</h1>',
		);

		$instance = array(
			'image_id'   => '',
			'image_size' => 'thumbnail',
			'link'       => '',
			'link_text'  => '',
			'new_window' => '',
			'title'      => '',
			'text'       => '',
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
		$upload = wp_upload_bits( basename( $filename ), null, $contents );
		$id = $this->make_attachment( $upload );

		$args = array(
			'before_widget' => '<aside id="%1$s" class="widget %2$s">',
			'after_widget'  => '</aside>',
			'before_title'  => '<h1 class="widget-title">',
			'after_title'   => '</h1>',
		);

		$instance = array(
			'image_id'   => $id,
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

		// May not work with cache enabled.
		$this->assertEquals( 'simpleimage', $widget->id_base );
		$this->assertEquals( $id, $widget->test_instance['image_id'] );
		$this->assertEquals( 'thumbnail', $widget->test_instance['image_size'] );
		$this->assertEquals( 'Title', $widget->test_instance['title_raw'] );
		$this->assertEquals( 'Text', $widget->test_instance['text_raw'] );
		$this->assertEquals( '<a href="http://example.com/">', $widget->test_instance['link_open'] );
		$this->assertEquals( '</a>', $widget->test_instance['link_close'] );
	}

	/**
	 *
	 */
	public function test_widget_update() {
		global $wp_widget_factory;

		$instance = array(
			'title'      => '<em>Title</em>',
			'image_id'   => '',
			'link'       => '',
			'link_text'  => '',
			'new_window' => '',
			'text'       => '',
		);

		$widget = $wp_widget_factory->widgets['Simple_Image_Widget_Test'];

		$new_instance = $widget->update( $instance, array() );
		$this->assertEquals( 'Title', $new_instance['title'] );
		$this->assertEquals( 0, $new_instance['image_id'] );
		$this->assertTrue( $new_instance['new_window'] );

		unset( $instance['new_window'] );
		$instance['text'] = '<em>Text</em>';

		$new_instance = $widget->update( $instance, array() );
		$this->assertFalse( $new_instance['new_window'] );
	}

	/**
	 *
	 */
	public function test_widget_templates() {
		global $wp_widget_factory;

		$args = array(
			'before_widget' => '',
			'after_widget'  => '',
			'before_title'  => '',
			'after_title'   => '',
		);

		$instance = array(
			'title'      => '',
			'text'       => '',
		);

		ob_start();
		$widget = $wp_widget_factory->widgets['Simple_Image_Widget_Test'];
		$widget->_set( -1 );
		$widget->widget( $args, $instance );
		ob_end_clean();

		$templates = $widget->test_get_template_names( $args, $instance );
		$this->assertEquals( 'widget.php', $templates[0] );

		ob_start();
		$args['id'] = 'sidebar-1';
		$widget->widget( $args, $instance );
		ob_end_clean();

		$templates = $widget->test_get_template_names( $args, $instance );
		$this->assertEquals( 'sidebar-1_widget.php', $templates[0] );
	}

	/**
	 *
	 */
	public function test_wiget_image_size_values() {
		global $wp_widget_factory;

		$filename = SIW_DIR_TESTDATA . '/images/blazer-six.png';
		$contents = file_get_contents( $filename );
		$upload = wp_upload_bits( basename( $filename ), null, $contents );
		$id = $this->make_attachment( $upload );

		$widget = $wp_widget_factory->widgets['Simple_Image_Widget_Test'];
		$sizes = $widget->get_image_sizes( $id );

		$this->assertEquals( 'Full Size (800&times;193)', $sizes['full'] );
		$this->assertEquals( 'Thumbnail (150&times;150)', $sizes['thumbnail'] );
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

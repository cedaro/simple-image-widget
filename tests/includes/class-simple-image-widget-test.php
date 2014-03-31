<?php

class Simple_Image_Widget_Test extends Simple_Image_Widget {
	public $test_args = array();
	public $test_instance = array();

	public function __construct() {
		parent::__construct();
	}

	public function render( $args, $instance ) {
		$this->test_args = $args;
		$this->test_instance = $instance;
		return parent::render( $args, $instance );
	}
	
	public function test_get_template_names( $args, $instance ) {
		return $this->get_template_names( $args, $instance );
	}
}

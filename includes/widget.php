<?php

namespace Tarosky\ProgrammableSearch;


/**
 * Search Widget
 */
class Widget extends \WP_Widget {

	/**
	 * Register widget.
	 */
	public function __construct() {
		parent::__construct( 'tps-widget', __( 'Google Search', 'tps' ), [
			'description' => __( 'Display search form with Google Programmable Search.', 'tps' ),
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function widget( $args, $instance ) {
		static $increment = 0;
		$increment++;
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'];
			echo esc_html( $instance['title'] );
			echo $args['after_title'];
		}
		echo tps_search_form( [
			'data-gname' => sprintf( 'tps-search-widget-%d', $increment ),
			'class'      => 'gcse-searchbox-only',
		] );
		echo $args['after_widget'];
	}

	/**
	 * From
	 *
	 * @param array $instance
	 * @return void
	 */
	public function form( $instance ) {
		$instance = wp_parse_args( $instance, [
			'title' => '',
		] );
		echo '<p>';
		printf(
			'<p><label>%s<br />%s</label></p>',
			esc_html__( 'Title', 'tps' ),
			sprintf(
				'<input type="text" name="%s" value="%s" placeholder="%s" />',
				esc_attr( $this->get_field_name( 'title' ) ),
				esc_attr( $instance['title'] ),
				esc_attr__( 'Search', 'tps' )
			)
		);
		echo '</p>';
	}
}

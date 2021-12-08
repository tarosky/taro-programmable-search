<?php
/**
 * Template related hooks.
 *
 * @package tps
 */

/**
 * Add search form to result page.
 *
 * @param string $content Post content
 * @return string
 */
add_filter( 'the_content', function( $content ) {
	if ( ! tps_use_search_engine() || get_the_ID() !== tps_get_search_engine_page() ) {
		return $content;
	}
	// Add shortcode.
	if ( ! has_shortcode( $content, 'tps_search_result' ) && ! has_block( 'tarosky/google-search' ) ) {
		$content .= "\n[tps_search_result]";
	}
	return $content;
}, 9 );

/**
 * Add shortcode for search result rendering.
 *
 * @param array  $atts    Attributes.
 * @param string $content Contents.
 * @return string
 */
add_shortcode( 'tps_search_result', function( $atts = [], $content = '' ) {
	return tps_get_search_result_script();
}  );

/**
 * Add shortcode for search form.
 *
 * @param array  $atts    Attributes.
 * @param string $content Contents.
 * @return string
 */
add_shortcode( 'tps_search_form', function( $atts = [], $content = '' ) {
	return tps_search_form( $atts, true );
} );

/**
 * Register widget.
 */
add_action( 'widgets_init', function() {
	register_widget( \Tarosky\ProgrammableSearch\Widget::class );
} );

/**
 * Register assets.
 */
add_action( 'init', function() {
	$json = tps_dir() . '/wp-dependencies.json';
	if ( ! file_exists( $json ) ) {
		return;
	}
	$assets = json_decode( file_get_contents( $json ), true );
	if ( ! $assets ) {
		return;
	}
	foreach ( $assets as $asset ) {
		if ( empty( $asset['path'] ) ) {
			continue;
		}
		$url = tps_url() . '/' . $asset['path'];
		switch ( $asset['ext'] ) {
			case 'css':
				wp_register_style( $asset['handle'], $url, $asset['deps'], $asset['hash'], $asset['media'] );
				break;
			case 'js':
				wp_register_script( $asset['handle'], $url, $asset['deps'], $asset['hash'], $asset['footer'] );
				if ( in_array( 'wp-i18n', $asset['deps'], true ) ) {
					wp_set_script_translations( $asset['handle'], 'tps' );
				}
				break;
		}
	}
}, 20 );

/**
 * Register blocks.
 */
add_action( 'init', function() {
	$name = 'tarosky/google-search';
	$attr = [
		'layout' => [
			'type'     => 'string',
			'required' => true,
			'default'  => 'both',
		],
	];
	wp_localize_script( 'tps-search-block', 'TpsSearchBlock', [
		'name'       => $name,
		'attributes' => $attr,
	] );
	register_block_type( $name, [
		'editor_script' => 'tps-search-block',
		'editor_style'  => 'tps-block',
		'attributes'    => $attr,
		'render_callback' => function( $attr = [], $content = '' ) {
			$output = sprintf( '<div class="wp-block-google-search has-layout-%s">', esc_attr( $attr['layout'] ) );
			if ( in_array( $attr['layout'], [ 'both', 'form' ], true ) ) {
				$output .= tps_search_form();
			}
			if ( in_array( $attr['layout'], [ 'both', 'result' ], true ) ) {
				$output .= tps_get_search_result_script();
			}
			$output .= '</div>';
			return $output;
		},
	] );
}, 21 );

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
	return tps_get_search_result_script( [
		'standalone' => ! tps_has_paired_search_form(),
	] );
}  );

/**
 * Add shortcode for search form.
 *
 * @param array  $atts    Attributes.
 * @param string $content Contents.
 * @return string
 */
add_shortcode( 'tps_search_form', function( $atts = [], $content = '' ) {
	$atts = is_array( $atts ) ? $atts : [];
	// Without results on the same page, Google requires the standalone search box.
	// Explicitly set class or data-gname are respected.
	if ( ! isset( $atts['class'] ) && ! isset( $atts['data-gname'] ) && ! tps_has_paired_search_result() ) {
		$atts['class']      = 'gcse-searchbox-only';
		$atts['data-gname'] = wp_unique_id( 'tps-search-form-' );
	}
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
		'editor_script'   => 'tps-search-block',
		'editor_style'    => 'tps-block',
		'attributes'      => $attr,
		'render_callback' => function( $attr = [], $content = '' ) {
			$output = sprintf( '<div class="wp-block-google-search has-layout-%s">', esc_attr( $attr['layout'] ) );
			if ( 'both' === $attr['layout'] ) {
				// When layout is set to both, data-gname for both searchbox and searchresults need to match.
				$gname   = wp_unique_id( 'tps-search-block-' );
				$output .= tps_search_form( [ 'data-gname' => $gname ] );
				$output .= tps_get_search_result_script( [ 'data-gname' => $gname ] );
			} elseif ( 'form' === $attr['layout'] ) {
				// Without results on the same page, Google requires the standalone search box.
				$output .= tps_search_form( [
					'data-gname' => wp_unique_id( 'tps-search-block-' ),
					'class'      => 'gcse-searchbox-only',
				] );
			} elseif ( 'result' === $attr['layout'] ) {
				$output .= tps_get_search_result_script( [ 'standalone' => true ] );
			}
			$output .= '</div>';
			return $output;
		},
	] );
}, 21 );

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
	if ( ! has_shortcode( $content, 'tps_search_result' ) ) {
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

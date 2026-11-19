<?php
/**
 * Utility Functions.
 *
 * @package tps
 */

/**
 * Get root directory.
 *
 * @return string
 */
function tps_dir() {
	return dirname( __DIR__ );
}

/**
 * Get URL.
 *
 * @return string
 */
function tps_url() {
	return untrailingslashit( plugin_dir_url( __DIR__ ) );
}

/**
 * Get version.
 *
 * @return string
 */
function tps_version() {
	static $version = null;
	if ( is_null( $version ) ) {
		$data    = get_file_data( dirname( __DIR__ ) . '/taro-programmable-search.php', [
			'version' => 'Version',
		] );
		$version = $data['version'];
	}
	return $version;
}

/**
 * Get search engine ID.
 *
 * @return string
 */
function tps_get_search_engine_id() {
	return get_option( 'tps_search_engine_id', '' );
}

/**
 * Search result page.
 *
 * @return int
 */
function tps_get_search_engine_page() {
	return (int) get_option( 'tps_search_result_page' );
}

/**
 * Should use custom search engine.
 *
 * @return bool
 */
function tps_use_search_engine() {
	return tps_get_search_engine_id() && tps_get_search_engine_page();
}


/**
 * Enqueue JavaScript for google search.
 *
 * The script is enqueued in the footer, because the content may be rendered more than once
 * (e.g. SEO plugins generating a description) and the output of such renders is discarded.
 *
 * @param bool $echo Deprecated. The script is always enqueued.
 * @return string Always empty, kept for backward compatibility.
 */
function tps_enqueue_google_search_script( $echo = true ) {
	if ( ! tps_use_search_engine() ) {
		return '';
	}
	wp_enqueue_script( 'tps-google-search', 'https://cse.google.com/cse.js?cx=' . rawurlencode( tps_get_search_engine_id() ), [], null, [
		'in_footer' => true,
		'strategy'  => 'async',
	] );
	return '';
}

/**
 * Render search results.
 *
 * @param array $attr         Attributes. 'standalone' renders results without a search box on the same page,
 *                            'data-gname' pairs the results with the search box of the same name.
 * @param bool  $print_script Print script.
 * @return string
 */
function tps_get_search_result_script( $attr = [], $print_script = true ) {
	$output = '';
	if ( $print_script ) {
		tps_enqueue_google_search_script( false );
	}
	$attr = wp_parse_args( $attr, [
		'standalone' => false,
		'data-gname' => '',
	] );
	if ( $attr['standalone'] ) {
		// Standalone results read the query from the URL.
		$gname   = $attr['data-gname'] ?: wp_unique_id( 'tps-search-results-' );
		$output .= sprintf( '<div class="gcse-searchresults-only" data-gname="%s" data-queryParameterName="s" data-linkTarget="_self"></div>', esc_attr( $gname ) );
	} else {
		$gname   = $attr['data-gname'] ?: 'tps-search';
		$output .= sprintf( '<div class="gcse-searchresults" data-gname="%s" data-linkTarget="_self"></div>', esc_attr( $gname ) );
	}
	return $output;
}

/**
 * Detect if the current post has a search form shortcode to pair the search results with.
 *
 * @return bool
 */
function tps_has_paired_search_form() {
	$post = get_post();
	return $post && has_shortcode( $post->post_content, 'tps_search_form' );
}

/**
 * Detect if the current post has search results to pair the search form with.
 *
 * @return bool
 */
function tps_has_paired_search_result() {
	$post = get_post();
	if ( ! $post ) {
		return false;
	}
	if ( has_shortcode( $post->post_content, 'tps_search_result' ) ) {
		return true;
	}
	// Results are appended to the result page if it has no block.
	return tps_use_search_engine() && tps_get_search_engine_page() === $post->ID && ! has_block( 'tarosky/google-search', $post );
}

/**
 * Render print script.
 *
 * @param array $args         Settings.
 * @param bool  $print_script If false, do not print script.
 * @string
 */
function tps_search_form( $args = [], $print_script = true ) {
	$result_page = tps_get_search_engine_page();
	if ( ! $result_page ) {
		return '';
	}
	$output = '';
	if ( $print_script ) {
		tps_enqueue_google_search_script( false );
	}
	$args       = wp_parse_args( $args, [
		'enableAutoComplete'      => true,
		'data-queryParameterName' => 's',
		'class'                   => 'gcse-searchbox',
		'data-gname'              => 'tps-search',
	] );
	$result_url = get_permalink( $result_page );
	$args       = array_merge( [
		'data-resultsUrl' => $result_url,
	], $args );
	$attributes = [];
	foreach ( $args as $key => $value ) {
		if ( $value ) {
			$attributes[] = sprintf( '%s="%s"', esc_html( $key ), esc_attr( $value ) );
		}
	}
	$output .= sprintf( '<div %s></div>', implode( ' ', $attributes ) );
	return $output;
}

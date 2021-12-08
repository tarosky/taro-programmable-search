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
 * @param bool $echo If false, only return string.
 * @return string
 */
function tps_enqueue_google_search_script( $echo = true ) {
	static $did = false;
	if ( ! tps_use_search_engine() || $did ) {
		return '';
	}
	$did    = true;
	$string = sprintf( '<script async src="https://cse.google.com/cse.js?cx=%s"></script>', esc_attr( tps_get_search_engine_id() ) );
	if ( $echo ) {
		echo $string;
	}
	return $string;
}

/**
 * Render search results.
 *
 * @param array $attr         Attributes(not used currently)
 * @param bool  $print_script Print script.
 * @return string
 */
function tps_get_search_result_script( $attr = [], $print_script = true ) {
	$output = '';
	if ( $print_script ) {
		$output .= tps_enqueue_google_search_script( false ) . "\n";
	}
	$output .= '<div class="gcse-searchresults" data-gname="tps-search" data-linkTarget="_self"></div>';
	return $output;
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
		$output .= tps_enqueue_google_search_script( false ) . "\n";
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

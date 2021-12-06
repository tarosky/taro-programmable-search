<?php
/**
 * Utility Functions.
 *
 * @package tps
 */


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
 */
function tps_enqueue_google_search_script() {
	if ( ! tps_use_search_engine() ) {
		return;
	}
	printf( '<script async src="https://cse.google.com/cse.js?cx=%s"></script>', esc_attr( tps_get_search_engine_id() ) );
}

/**
 * Render search results.
 */
function tps_get_search_result_script() {
	tps_enqueue_google_search_script();
	return '<div class="gcse-searchresults-only" data-gname="tsp-search-result" data-linkTarget="_self"></div>';
}

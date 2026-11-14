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
 * Get taxonomies whose terms are output as meta tags.
 *
 * @return string[]
 */
function tps_get_meta_taxonomies() {
	return array_values( array_filter( (array) get_option( 'tps_meta_taxonomies', [] ), 'taxonomy_exists' ) );
}

/**
 * Get meta tags for the terms of a post.
 *
 * Google extracts meta tags into the "metatags" PageMap, so search results can be
 * filtered with e.g. more:pagemap:metatags-tps-category:term12
 *
 * @param null|int|WP_Post $post Post object.
 * @return array Meta name => content.
 */
function tps_get_term_meta( $post = null ) {
	$post = get_post( $post );
	$meta = [];
	if ( ! $post ) {
		return $meta;
	}
	foreach ( tps_get_meta_taxonomies() as $taxonomy ) {
		$terms = get_the_terms( $post, $taxonomy );
		if ( ! $terms || is_wp_error( $terms ) ) {
			continue;
		}
		$meta[ tps_get_term_meta_name( $taxonomy ) ] = implode( ',', array_map( 'tps_get_term_filter_value', $terms ) );
	}
	return $meta;
}

/**
 * Get the meta name for the terms of a taxonomy.
 *
 * @param string $taxonomy Taxonomy name.
 * @return string
 */
function tps_get_term_meta_name( $taxonomy ) {
	// Underscores are not documented as valid in meta names.
	return 'tps-' . str_replace( '_', '-', $taxonomy );
}

/**
 * Get the value to filter search results by a term.
 *
 * Term IDs are used because slugs can change.
 *
 * @param WP_Term $term Term object.
 * @return string
 */
function tps_get_term_filter_value( $term ) {
	return 'term' . $term->term_id;
}

/**
 * Render a select box to filter search results by terms.
 *
 * @param string $taxonomy Taxonomy name. Must be one of the taxonomies output as meta tags.
 * @param string $gname    data-gname of the search results to filter.
 * @return string
 */
function tps_search_filter( $taxonomy, $gname ) {
	if ( ! $taxonomy || ! in_array( $taxonomy, tps_get_meta_taxonomies(), true ) ) {
		return '';
	}
	$terms = get_terms( [
		'taxonomy'   => $taxonomy,
		'hide_empty' => true,
	] );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return '';
	}
	tps_enqueue_search_filter_script();
	$labels  = get_taxonomy( $taxonomy )->labels;
	$options = sprintf( '<option value="">%s</option>', esc_html( $labels->all_items ) );
	foreach ( $terms as $term ) {
		$options .= sprintf( '<option value="%s">%s</option>', esc_attr( tps_get_term_filter_value( $term ) ), esc_html( $term->name ) );
	}
	return sprintf(
		'<p class="tps-search-filter"><select class="tps-search-filter-select" data-gname="%s" data-meta="%s" aria-label="%s">%s</select></p>',
		esc_attr( $gname ),
		esc_attr( tps_get_term_meta_name( $taxonomy ) ),
		esc_attr( $labels->name ),
		$options
	);
}

/**
 * Enqueue the script to filter search results.
 *
 * The filter registers search callbacks, which Google's script only reads when it loads.
 * So Google's script has to depend on it.
 */
function tps_enqueue_search_filter_script() {
	tps_enqueue_google_search_script();
	wp_enqueue_script( 'tps-search-filter' );
	$google = wp_scripts()->query( 'tps-google-search' );
	if ( $google && ! in_array( 'tps-search-filter', $google->deps, true ) ) {
		$google->deps[] = 'tps-search-filter';
	}
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

<?php
/**
 * Google Programmable search API integration.
 *
 * @package tps
 */

/**
 * Register hooks.
 */
add_action( 'admin_init', function() {
	// Not on Ajax.
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return;
	}
	// Register fields.
	add_settings_field( 'tps_search_engine_id', __( 'Search Engine ID', 'tps' ), function() {
		?>
		<p>
			<input type="text" name="tps_search_engine_id" class="regular-text"
				value="<?php echo esc_attr( get_option( 'tps_search_engine_id' ) ); ?>" /><br />
			<span class="description">
				<?php
				// translators: %s is URL of console.
				echo wp_kses_post( sprintf( __( 'Create search engine <a href="%s">here</a>.', 'tps' ), esc_url( 'https://programmablesearchengine.google.com/' ) ) );
				?>
			</span>
		</p>
		<?php
	}, 'reading' );
	// Register setting.
	register_setting( 'reading', 'tps_search_engine_id' );

	// Search page
	add_settings_field( 'tps_search_result_page', __( 'Search Result Page', 'tps' ), function() {
		$query   = new \WP_Query( [
			'post_type'      => 'page',
			'post_status'    => 'any',
			'posts_per_page' => 500,
		] );
		$current = get_option( 'tps_search_result_page' );
		?>
		<select name="tps_search_result_page">
			<option value=""<?php selected( '', $current ); ?>><?php esc_html_e( 'Not Set', 'tps' ); ?></option>
			<?php
			if ( $query->have_posts() ) {
				foreach ( $query->posts as $post ) {
					printf(
						'<option value="%s"%s>%s</option>',
						esc_attr( $post->ID ),
						selected( $post->ID, $current, false ),
						esc_html( get_the_title( $post ) )
					);
				}
			}
			?>
		</select>
		<p>
			<?php
			echo wp_kses_post( sprintf(
				// translators: %s is shortcode.
				__( 'Enter shortcode %s in the post content, or else whole content will be replaced with search result.', 'tps' ),
				'<code>[tps_search_result]</code>'
			) );
			?>
		</p>
		<?php
	}, 'reading' );
	// Register setting.
	register_setting( 'reading', 'tps_search_result_page' );
} );

/**
 * If this is search result page, change query.
 */
add_action( 'pre_get_posts', function( WP_Query &$wp_query ) {
	if ( ! $wp_query->is_main_query() || is_admin() ) {
		return;
	}
	// Remove query vars if set.
	$search_page = tps_get_search_engine_page();
	if ( ! $search_page ) {
		return;
	}
	// If this is not a page, skip.
	if ( ! $wp_query->is_page() ) {
		return;
	}
	$s = $wp_query->get( 's' );
	if ( ! $s ) {
		// No query set, skip.
		return;
	}
	// Get search page.
	$search_page = get_post( $search_page );
	if ( ! $search_page ) {
		// No search page set.
		return;
	}
	if ( $search_page->post_name !== $wp_query->get( 'pagename' ) ) {
		// This is not search page skip.
		return;
	}
	// Restore query for Google search page.
	$wp_query->set( 'old_search_query', $wp_query->get( 's' ) );
	$wp_query->set( 's', '' );
}, 20 );

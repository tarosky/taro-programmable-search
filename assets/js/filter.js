/*!
 * Filter search results by terms.
 *
 * Must be loaded before Google's script, which reads the search callbacks only once.
 *
 * @handle tps-search-filter
 */

const operator = /\s*more:pagemap:metatags-tps-[a-z0-9-]+:\S+/g;

// Last query entered by the user, per search element.
const lastQueries = {};

const getFilter = ( gname ) => document.querySelector( `.tps-search-filter-select[data-gname="${ gname }"]` );

const gcse = ( window.__gcse = window.__gcse || {} );
gcse.searchCallbacks = gcse.searchCallbacks || {};
gcse.searchCallbacks.web = gcse.searchCallbacks.web || {};
const previousCallback = gcse.searchCallbacks.web.starting;

// Temp debug log
const orig = window.__gcse.searchCallbacks.web.starting;
window.__gcse.searchCallbacks.web.starting = (g, q) => { const r = orig(g, q); console.log('starting', g, q, '→', r); return r; };

gcse.searchCallbacks.web.starting = ( gname, query ) => {
	if ( 'function' === typeof previousCallback ) {
		const result = previousCallback( gname, query );
		if ( 'string' === typeof result ) {
			query = result;
		}
	}
	const filter = getFilter( gname );
	if ( ! filter ) {
		return query;
	}
	// Remove the operator in case Google shows it in the search box.
	query = query.replace( operator, '' ).trim();
	lastQueries[ gname ] = query;
	if ( ! filter.value ) {
		return query;
	}
	return `${ query } more:pagemap:metatags-${ filter.dataset.meta }:${ filter.value }`;
};

// Search again when the filter changes.
document.addEventListener( 'change', ( event ) => {
	const filter = event.target;
	if ( ! filter.matches( '.tps-search-filter-select' ) ) {
		return;
	}
	const query = lastQueries[ filter.dataset.gname ];
	if ( ! query || ! window.google || ! window.google.search || ! window.google.search.cse ) {
		return;
	}
	const element = window.google.search.cse.element.getElement( filter.dataset.gname );
	if ( element ) {
		element.execute( query );
	}
} );

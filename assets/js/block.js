/*!
 * Google Programmable search block.
 *
 * @handle tps-search-block
 * @deps wp-i18n, wp-components, wp-blocks, wp-block-editor, wp-server-side-render, wp-compose, wp-data
 */

/* global TpsSearchBlock:false */

const { registerBlockType } = wp.blocks;
const { __ } = wp.i18n;
const { InspectorControls } = wp.blockEditor;
const { PanelBody, SelectControl } = wp.components;

registerBlockType( TpsSearchBlock.name, {

	title: __( 'Google Search', 'tps' ),

	icon: 'google',

	category: 'widgets',

	keywords: [ 'search' ],

	attributes: TpsSearchBlock.attributes,

	description: __( 'Display Search block.', 'tps' ),

	edit( { attributes, setAttributes } ) {
		const options = [
			{
				value: 'both',
				label: __( 'Search Form & Result', 'tps' ),
			},
			{
				value: 'result',
				label: __( 'Search Result Only', 'tps' ),
			},
			{
				value: 'form',
				label: __( 'Search Form Only', 'tps' ),
			},
		];
		let curLabel = '';
		options.forEach( ( option ) => {
			if ( option.value === attributes.layout) {
				curLabel = option.label;
			}
		} );
		return (
			<>
				<InspectorControls>
					<PanelBody defaultOpen={ true } title={ __( 'Form Setting', 'taro-taxonomy-blocks' ) } >
						<SelectControl label={ __( 'Layout', 'tps' ) } value={ attributes.layout } options={ options } onChange={ ( layout ) => {
							setAttributes( { layout } );
						} } />
					</PanelBody>
				</InspectorControls>

				<div className="tps-search-block">
					<p className="tps-search-block-legend">{ curLabel }</p>
					<p className="description">
						{ __( 'This is not what actually rendered, because Google Programmable Search Engine renders the search from via JavaScript and they are customizable via Console. Please check the appearance by preview.', 'tps' ) }
					</p>
					{ ( 0 <= [ 'both', 'form' ].indexOf( attributes.layout ) ) && (
						<div className="tps-search-block-form">
							<input type="text" className="tps-search-block-input" placeholder={ __( 'Input and search...', 'tps' ) } />
						</div>
					) }
					{ ( 0 <= [ 'both', 'result' ].indexOf( attributes.layout ) ) && (
						<div className="tps-search-block-result">
							{ [ __( '1st Result', 'tps' ), __( '2nd Result', 'tps' ), __( '3rd Result', 'tps' ) ].map( ( result, index ) => {
								return (
									<div className="tps-search-block-item" key={ `tps-result-${ index }` }>
										<div className="tps-search-block-item-title">
											{ result }
										</div>
										<div className="tps-search-block-item-description">
											{ __( 'Here comes the description of the page. It depends on Google\'s algorithm.', 'tps' ) }
										</div>
									</div>
								);
							} ) }
						</div>
					) }
				</div>
			</>
		);
	},

	save() {
		return null;
	},
} );

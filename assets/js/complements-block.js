( function ( wp ) {
	const el = wp.element.createElement;
	const { useEffect, useState } = wp.element;
	const { InspectorControls, useBlockProps } = wp.blockEditor;
	const { ComboboxControl, PanelBody, Notice, Spinner } = wp.components;
	const ServerSideRender = wp.serverSideRender;
	const __ = wp.i18n.__;

	wp.blocks.registerBlockType( 'hamkharid/complements', {
		title: __( 'Hamkharid complements', 'hamkharid' ),
		description: __( 'Show category-based complementary products for a selected product.', 'hamkharid' ),
		icon: 'cart',
		category: 'widgets',
		attributes: {
			productId: { type: 'integer', default: 0 },
		},
		edit: function ( props ) {
			const [ search, setSearch ] = useState( '' );
			const [ options, setOptions ] = useState( [] );
			const [ loading, setLoading ] = useState( false );
			const [ error, setError ] = useState( '' );

			useEffect( function () {
				let active = true;
				setLoading( true );
				const selectedId = props.attributes.productId || 0;
				const selectedQuery = selectedId ? '&product_id=' + encodeURIComponent( selectedId ) : '';
				wp.apiFetch( { path: '/hamkharid/v1/products?search=' + encodeURIComponent( search ) + selectedQuery } )
					.then( function ( products ) {
						if ( active ) {
							setOptions( products.map( function ( product ) {
								return { value: String( product.id ), label: product.label };
							} ) );
							setError( '' );
						}
					} )
					.catch( function () {
						if ( active ) {
							setError( __( 'Products could not be loaded. Check your permissions and try again.', 'hamkharid' ) );
						}
					} )
					.finally( function () {
						if ( active ) {
							setLoading( false );
						}
					} );

				return function () { active = false; };
			}, [ search, props.attributes.productId ] );

			const selected = props.attributes.productId ? String( props.attributes.productId ) : '';
			const choices = options;

			return el(
				wp.element.Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Product', 'hamkharid' ), initialOpen: true },
						el( ComboboxControl, {
							label: __( 'Product for recommendations', 'hamkharid' ),
							value: selected,
							options: choices,
							onFilterValueChange: setSearch,
							onChange: function ( value ) {
								props.setAttributes( { productId: parseInt( value, 10 ) || 0 } );
							},
							help: __( 'Leave empty to use the product context in a single-product template.', 'hamkharid' ),
						} ),
						loading ? el( Spinner ) : null,
						error ? el( Notice, { status: 'error', isDismissible: false }, error ) : null
					)
				),
				el(
					'div',
					useBlockProps(),
					selected || props.context.postId
						? el( ServerSideRender, { block: 'hamkharid/complements', attributes: props.attributes } )
						: el( Notice, { status: 'info', isDismissible: false }, __( 'Choose a product or place this block in a single-product template.', 'hamkharid' ) )
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );

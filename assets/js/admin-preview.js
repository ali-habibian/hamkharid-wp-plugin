( function () {
	const __ = window.wp.i18n.__;
	const search = document.getElementById( 'hamkharid-preview-product-search' );
	const productId = document.getElementById( 'hamkharid-preview-product-id' );
	const results = document.getElementById( 'hamkharid-preview-product-results' );
	if ( ! search || ! productId || ! results || ! window.hamkharidPreview ) {
		return;
	}

	let timer = null;
	let request = null;

	function showMessage( message ) {
		results.replaceChildren();
		if ( message ) {
			const item = document.createElement( 'p' );
			item.textContent = message;
			results.appendChild( item );
		}
	}

	function renderProducts( products ) {
		results.replaceChildren();
		products.forEach( function ( product ) {
			const item = document.createElement( 'div' );
			const button = document.createElement( 'button' );
			button.type = 'button';
			button.className = 'button-link';
			button.textContent = product.label;
			button.addEventListener( 'click', function () {
				productId.value = String( product.id );
				search.value = product.label;
				results.replaceChildren();
			} );
			item.appendChild( button );
			results.appendChild( item );
		} );
		if ( products.length === 0 ) {
			showMessage( __( 'No products found.', 'hamkharid' ) );
		}
	}

	function loadProducts( query, selectedId ) {
		if ( request ) {
			request.abort();
		}
		request = new AbortController();
		const url = new URL( window.hamkharidPreview.restUrl );
		url.searchParams.set( 'search', query );
		if ( selectedId ) {
			url.searchParams.set( 'product_id', selectedId );
		}
		showMessage( __( 'Searching…', 'hamkharid' ) );
		fetch( url.toString(), {
			credentials: 'same-origin',
			headers: { 'X-WP-Nonce': window.hamkharidPreview.nonce },
			signal: request.signal,
		} )
			.then( function ( response ) {
				if ( ! response.ok ) {
					throw new Error( __( 'Product search failed.', 'hamkharid' ) );
				}
				return response.json();
			} )
			.then( renderProducts )
			.catch( function ( error ) {
				if ( 'AbortError' !== error.name ) {
					showMessage( __( 'Products could not be loaded. Check your permissions and try again.', 'hamkharid' ) );
				}
			} );
	}

	search.addEventListener( 'input', function () {
		productId.value = '';
		window.clearTimeout( timer );
		const query = search.value.trim();
		if ( query.length < 2 ) {
			showMessage( '' );
			return;
		}
		timer = window.setTimeout( function () { loadProducts( query, '' ); }, 250 );
	} );

	if ( productId.value ) {
		loadProducts( '', productId.value );
	}
}() );

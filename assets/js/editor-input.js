/*!
 * Editor input.
 *
 * @handle tscp-editor-input
 * @deps wp-plugins, wp-edit-post, wp-components, wp-data, wp-api-fetch, wp-i18n, wp-compose, wp-element
 */

/* global TscpEditorInput: false */

const { registerPlugin } = wp.plugins;
const { PluginPostStatusInfo } = wp.editPost;
const { ToggleControl, TextControl, Spinner } = wp.components;
const { withState } = wp.compose;
const { useEffect } = wp.element;
const { select, dispatch } = wp.data;
const { apiFetch } = wp;
const { __, sprintf } = wp.i18n;

const toLocalDate = ( date ) => {
	if ( date.match( /(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):\d{2}/ ) ) {
		return sprintf( '%04d-%02d-%02dT%02d:%02d', RegExp.$1, RegExp.$2, RegExp.$3, RegExp.$4, RegExp.$5 );
	} else {
		return '';
	}
};

const toDate = ( localDate ) => {
	if ( localDate.match( /(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/ ) ) {
		return sprintf( '%04d-%02d-%02d %02d:%02d:59', RegExp.$1, RegExp.$2, RegExp.$3, RegExp.$4, RegExp.$5 );
	} else {
		return '';
	}
};

const notice = ( message, status = 'success' ) => {
	dispatch( 'core/notices' ).createNotice( status, message, {
		type: 'snackbar',
		isDismissible: true,
	} ).then( ( { notice } ) => {
		setTimeout( () => {
			dispatch( 'core/notices' ).removeNotice( notice.id );
		}, 2000 );
	} );
}

let storedUpdated = null;

const TscpPostExpireBox = withState( {
	active: false,
	date: '',
	loading: true,
	timer: null,
} )( ( { setState, loading, active, date, timer } ) => {
	const postType = select( 'core/editor' ).getCurrentPostType();
	if ( 0 > TscpEditorInput.postTypes.indexOf( postType ) ) {
		// This is not supported.
		return null;
	}
	const postId = select( 'core/editor' ).getCurrentPostId();
	const path = sprintf( 'clockwork/v1/%1$s/%2$d/expiration', postType, postId );

	const sync = ( a, d ) => {
		if ( timer ) {
			clearTimeout( timer );
		}
		setTimeout( () => {
			apiFetch( {
				path,
				method: 'post',
				data: {
					should: a,
					expires: d,
				}
			} ).then( ( res ) => {
				notice( res.message );
			} ).catch( ( res ) => {
				notice( res.message, 'error' );
			} );
		}, 500 );
		console.log( 'SYNCING: ', a, d );
	};

	// Initialize.
	useEffect( () => {
		if ( storedUpdated === null ) {
			storedUpdated = '';
			apiFetch( {
				path
			} ).then( res => {
				setState( {
					loading: false,
					active: res.should_expires,
					date: toLocalDate( res.expires ),
				} );
			} ).catch( res => {
				setState( { loading: false }, () => {
					notice( res.message, 'error' );
				} );
			} );
		}
	} );

	return (
		<PluginPostStatusInfo className="tscp-time-input">
			{ loading && (
				<p style={ { position: 'absolute', top: 0, right: 0 } }>
					<Spinner />
				</p>
			) }
			<ToggleControl
				className="tscp-time-input-toggle"
				label={ __( 'Expires at specified time', 'tscp' ) }
				checked={ active }
				onChange={ ( isActive ) => {
					setState( { active: isActive }, () => {
						sync( isActive, toDate( date ) );
					} )
				} }
			/>
			{ active && (
				<TextControl label={ __( 'Expires At', 'tscp' ) } className="tscp-time-input-date" type="datetime-local"
					value={ date }
					onChange={ ( newDate => {
						setState( { date: newDate }, () => {
							sync( active, toDate( newDate ) );
						} );
					} ) }
				/>
			) }
		</PluginPostStatusInfo>
	);
} );

registerPlugin( 'tscp-post-expire-box', { render: TscpPostExpireBox } );

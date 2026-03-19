/*!
 * Editor input.
 *
 * @handle tscp-editor-input
 * @deps wp-plugins, wp-edit-post, wp-components, wp-data, wp-api-fetch, wp-i18n, wp-element
 */

/* global TscpEditorInput: false */

const { registerPlugin } = wp.plugins;
const { PluginPostStatusInfo } = wp.editPost;
const { ToggleControl, TextControl, Spinner } = wp.components;
const { useEffect, useState } = wp.element;
const { select, dispatch } = wp.data;
const { apiFetch } = wp;
const { __, sprintf } = wp.i18n;

const pad = ( value ) => String( value ).padStart( 2, '0' );

const toLocalDate = ( date ) => {
	const m = date.match( /(\d{4})-(\d{1,2})-(\d{1,2}) (\d{1,2}):(\d{1,2}):\d{1,2}/ );
	if ( m ) {
		return `${ m[ 1 ] }-${ pad( m[ 2 ] ) }-${ pad( m[ 3 ] ) }T${ pad( m[ 4 ] ) }:${ pad( m[ 5 ] ) }`;
	}
	return '';
};

const toDate = ( localDate ) => {
	const m = localDate.match( /(\d{4})-(\d{1,2})-(\d{1,2})T(\d{1,2}):(\d{1,2})/ );
	if ( m ) {
		return `${ m[ 1 ] }-${ pad( m[ 2 ] ) }-${ pad( m[ 3 ] ) } ${ pad( m[ 4 ] ) }:${ pad( m[ 5 ] ) }:59`;
	}
	return '';
};

const notify = ( message, status = 'success' ) => {
	dispatch( 'core/notices' ).createNotice( status, message, {
		type: 'snackbar',
		isDismissible: true,
	} ).then( ( { notice } ) => {
		setTimeout( () => {
			dispatch( 'core/notices' ).removeNotice( notice.id );
		}, 2000 );
	} );
};

let storedUpdated = null;

const TscpPostExpireBox = () => {
	// Nescessary variables.
	const postType = select( 'core/editor' ).getCurrentPostType();
	const [ active, setActive ] = useState( false );
	const [ date, setDate ] = useState( '' );
	const [ loading, setLoading ] = useState( false );
	const [ timer, setTimer ] = useState( null );
	if ( 0 > TscpEditorInput.postTypes.indexOf( postType ) ) {
		// This is not supported.
		return null;
	}
	const postId = select( 'core/editor' ).getCurrentPostId();
	const path = sprintf( 'clockwork/v1/%1$s/%2$d/expiration', postType, postId );

	const sync = ( a, d ) => {
		if ( timer ) {
			clearTimeout( timer );
			setTimer( null );
		}
		setTimer( setTimeout( () => {
			apiFetch( {
				path,
				method: 'post',
				data: {
					should: a,
					expires: d,
				},
			} ).then( ( res ) => {
				notify( res.message );
			} ).catch( ( res ) => {
				notify( res.message, 'error' );
			} );
		}, 500 ) );
	};

	// Initialize.
	// eslint-disable-next-line react-hooks/rules-of-hooks
	useEffect( () => {
		if ( storedUpdated === null ) {
			storedUpdated = '';
			apiFetch( {
				path,
			} ).then( ( res ) => {
				setLoading( false );
				setActive( res.should_expires );
				setDate( toLocalDate( res.expires ) );
			} ).catch( ( res ) => {
				setLoading( false );
				notify( res.message, 'error' );
			} );
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	return (
		<PluginPostStatusInfo className="tscp-time-input">
			{ loading && (
				<p style={ { position: 'absolute', top: 0, right: 0 } }>
					<Spinner />
				</p>
			) }
			<ToggleControl
				className="tscp-time-input-toggle"
				label={ __( 'Expires at specified time', 'taro-clockwork-post' ) }
				checked={ active }
				onChange={ ( isActive ) => {
					setActive( isActive );
					sync( isActive, toDate( date ) );
				} }
			/>
			{ active && (
				<TextControl label={ __( 'Expires At', 'taro-clockwork-post' ) } className="tscp-time-input-date" type="datetime-local"
					value={ date }
					onChange={ ( ( newDate ) => {
						setDate( newDate );
						sync( active, toDate( newDate ) );
					} ) }
				/>
			) }
		</PluginPostStatusInfo>
	);
};

registerPlugin( 'tscp-post-expire-box', { render: TscpPostExpireBox } );

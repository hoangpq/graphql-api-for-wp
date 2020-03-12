import GraphiQL from 'graphiql';
import fetch from 'isomorphic-fetch';
import 'graphiql/graphiql.css';
import './style.scss';

const graphQLFetcher = ( graphQLParams ) => {
	return fetch( window.location.origin + '/api/graphql', {
		method: 'post',
		headers: { 'Content-Type': 'application/json' },
		body: JSON.stringify( graphQLParams ),
	} ).then( ( response ) => response.json() );
}

const EditBlock = ( props ) => {
	const {
		attributes: { query, variables },
		setAttributes,
		className,
	} = props;
	const onEditQuery = ( newValue ) =>
		setAttributes( { query: newValue } );
	const onEditVariables = ( newValue ) =>
		setAttributes( { variables: newValue } );
	return (
		<div className={ className }>
			<GraphiQL
				fetcher={ graphQLFetcher }
				query={ query }
				variables={ variables }
				onEditQuery={ onEditQuery }
				onEditVariables={ onEditVariables }
				docExplorerOpen={ false }
			/>
		</div>
	);
}

export default EditBlock;

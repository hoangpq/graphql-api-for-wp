/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { RadioControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { DEFAULT_SCHEMA_MODE, PUBLIC_SCHEMA_MODE, PRIVATE_SCHEMA_MODE } from './schema-modes';

const SchemaModeControl = ( props ) => {
	const {
		className,
		isSelected,
		setAttributes,
		attributes,
		attributeName,
		addDefault = false,
		defaultValue = addDefault ? DEFAULT_SCHEMA_MODE : PUBLIC_SCHEMA_MODE,
	} = props;
	const schemaMode = attributes[ attributeName ] || defaultValue;
	const options = (addDefault ?
		[
			{
				label: __('Default', 'graphql-api'),
				value: DEFAULT_SCHEMA_MODE,
			},
		] :
		[]
	).concat(
		[
			{
				label: __('Public', 'graphql-api'),
				value: PUBLIC_SCHEMA_MODE,
			},
			{
				label: __('Private', 'graphql-api'),
				value: PRIVATE_SCHEMA_MODE,
			},
		]
	);
	return (
		<>
			{ isSelected &&
				<RadioControl
					{ ...props }
					options={ options }
					selected={ schemaMode }
					onChange={ newValue => (
						setAttributes( {
							[ attributeName ]: newValue
						} )
					)}
				/>
			}
			{ !isSelected && (
				<div className={ className+'__read'}>
					{ (addDefault && schemaMode == DEFAULT_SCHEMA_MODE) &&
						<span>🟡 { __('Default', 'graphql-api') }</span>
					}
					{ (schemaMode == PUBLIC_SCHEMA_MODE) &&
						<span>⚪️ { __('Public', 'graphql-api') }</span>
					}
					{ (schemaMode == PRIVATE_SCHEMA_MODE) &&
						<span>⚫️ { __('Private', 'graphql-api') }</span>
					}
				</div>
			) }
		</>
	);
}

export default SchemaModeControl;

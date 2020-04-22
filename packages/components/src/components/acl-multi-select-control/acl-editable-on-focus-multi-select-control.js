/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AccessControlListMultiSelectControl from './acl-multi-select-control';
import AccessControlListPrintout from './acl-printout';

const AccessControlListEditableOnFocusMultiSelectControl = ( props ) => {
	const { setAttributes, isSelected, attributes: { accessControlLists }, accessControlComponentClassName } = props;
	const className = 'graphql-api-access-control-list-select';
	return (
		<div className={ className }>
			<div className={ accessControlComponentClassName }>
				{ isSelected &&
					<AccessControlListMultiSelectControl
						{ ...props }
						accessControlLists={ accessControlLists }
						setAttributes={ setAttributes }
						className={ className }
					/>
				}
				{ !isSelected && (
					<AccessControlListPrintout
						{ ...props }
						// accessControlListEntries={ accessControlListEntries }
						accessControlLists={ accessControlLists }
						className={ className }
					/>
				) }
			</div>
		</div>
	);
}

export default AccessControlListEditableOnFocusMultiSelectControl;
/**
 * WordPress deps
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, Notice } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import './editor.css';
import './style.css';

/**
 * The Edit component runs in the editor only.
 * - We query "company_list" posts via core data store.
 * - Let the editor pick one; we save the id in attributes.listId
 * - Frontend uses PHP render_callback to output HTML.
 */
export function Edit( { attributes, setAttributes } ) {
	const { listId } = attributes;
	const blockProps = useBlockProps();

	// Fetch all company_list posts for the dropdown
	const lists = useSelect( ( select ) => {
		return select( 'core' ).getEntityRecords( 'postType', 'company_list', { per_page: -1, orderby: 'title', order: 'asc' } ) || [];
	}, [] );

	const options = [
		{ label: __('— Select a list —','widget-company-directory'), value: '' },
		...lists.map( l => ({ label: l.title?.rendered || `(${l.id})`, value: String(l.id) }) ),
	];

	const selected = lists.find( l => String(l.id) === String(listId) );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __('Company List Settings','widget-company-directory') } initialOpen={true}>
					<SelectControl
						label={ __('Choose a list','widget-company-directory') }
						value={ listId }
						options={ options }
						onChange={ (val) => setAttributes({ listId: val }) }
					/>
				</PanelBody>
			</InspectorControls>

			<div className="company-list-placeholder">
				<h3>{ __('Company List Block','widget-company-directory') }</h3>
				{ !listId && <p>{ __('Select a list in the sidebar (Block settings → Company List Settings).','widget-company-directory') }</p> }
				{ listId && !selected && <Notice status="warning" isDismissible={ false }>{ __('The selected list was not found.','widget-company-directory') }</Notice> }
				{ selected && (
					<p>
						<strong>{ __('Selected List:','widget-company-directory') }</strong>{' '}
						{ selected.title?.rendered || selected.id }
					</p>
				) }
				<p><strong>Frontend:</strong> { __('Rendered by PHP for accurate, fast output.','widget-company-directory') }</p>
			</div>
		</div>
	);
}

export function save() {
	// Dynamic block → save returns null (PHP renders on frontend)
	return null;
}

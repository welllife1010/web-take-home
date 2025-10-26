import { registerBlockType } from '@wordpress/blocks';
import { Edit } from './edit';
import './editor.css';
import './style.css';

registerBlockType('widget-company-directory/company-list', {
	edit: Edit,
	save: () => null, // dynamic block rendered in PHP
});

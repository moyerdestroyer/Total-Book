import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import './style.scss';

registerBlockType('ttbp/book-display', {
	edit: () => {
		const blockProps = useBlockProps();
		return (
			<div {...blockProps}>
				<div style={{ padding: '20px', border: '1px dashed #ccc', textAlign: 'center' }}>
					<p><strong>Book Display Block</strong></p>
					<p style={{ fontSize: '12px', color: '#666' }}>
						This block will display the book e-reader when viewed on a book page! For best results, add this block to the book page template.
					</p>
				</div>
			</div>
		);
	},
	// No save function - this is a dynamic block
});

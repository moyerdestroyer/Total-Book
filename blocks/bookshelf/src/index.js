import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import { PanelBody, RangeControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from '../block.json';
import './style.scss';

registerBlockType(metadata.name, {
	edit: ({ attributes = {}, setAttributes }) => {
		const { count = 6, showExcerpt = true } = attributes;
		const blockProps = useBlockProps({
			className: 'ttbp-bookshelf-block',
		});

		return (
			<>
				<InspectorControls>
					<PanelBody title={__('Bookshelf Settings', 'the-total-book-project')} initialOpen={true}>
						<RangeControl
							label={__('Number of Books', 'the-total-book-project')}
							value={count}
							onChange={(value) => setAttributes({ count: value })}
							min={1}
							max={20}
						/>
						<ToggleControl
							label={__('Show Excerpt', 'the-total-book-project')}
							checked={showExcerpt}
							onChange={(value) => setAttributes({ showExcerpt: value })}
						/>
					</PanelBody>
				</InspectorControls>
				<div {...blockProps}>
					<ServerSideRender
						block={metadata.name}
						attributes={{
							count: count,
							showExcerpt: showExcerpt,
						}}
					/>
				</div>
			</>
		);
	},
	save: () => {
		return null; // Server-side rendered
	},
});


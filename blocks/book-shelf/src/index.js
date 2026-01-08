import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, RangeControl, ToggleControl, TextControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import './style.scss';

registerBlockType('ttbp/book-shelf', {
	edit: ({ attributes, setAttributes }) => {
		const blockProps = useBlockProps();
		const { category, limit, orderby, order, showMeta, showExcerpt, columns, template } = attributes;
		const [categories, setCategories] = useState([]);
		const [categoriesLoading, setCategoriesLoading] = useState(true);
		const [books, setBooks] = useState([]);
		const [booksLoading, setBooksLoading] = useState(true);

		// Fetch categories on mount
		useEffect(() => {
			apiFetch({ path: '/ttbp/v1/categories' })
				.then((cats) => {
					setCategories(cats || []);
					setCategoriesLoading(false);
				})
				.catch(() => {
					setCategories([]);
					setCategoriesLoading(false);
				});
		}, []);

		// Fetch books when attributes change
		useEffect(() => {
			setBooksLoading(true);
			const categoryParam = category && category.length > 0 ? category.join(',') : '';
			const queryParams = new URLSearchParams({
				limit: limit.toString(),
				orderby: orderby,
				order: order,
			});
			if (categoryParam) {
				queryParams.append('category', categoryParam);
			}

			apiFetch({ path: `/ttbp/v1/books?${queryParams.toString()}` })
				.then((booksData) => {
					setBooks(booksData || []);
					setBooksLoading(false);
				})
				.catch(() => {
					setBooks([]);
					setBooksLoading(false);
				});
		}, [category, limit, orderby, order]);

		const categoryOptions = [
			{ label: 'All Categories', value: '' }
		].concat(
			categories.map((cat) => ({
				label: `${cat.name} (${cat.count})`,
				value: cat.slug
			}))
		);

		// Handle multi-select categories
		const handleCategoryChange = (value) => {
			if (Array.isArray(value)) {
				setAttributes({ category: value });
			} else {
				setAttributes({ category: value ? [value] : [] });
			}
		};

		return (
			<>
				<InspectorControls>
					<PanelBody title="Book Query" initialOpen={true}>
						<SelectControl
							label="Category"
							value={category && category.length > 0 ? category[0] : ''}
							options={categoryOptions}
							onChange={(value) => handleCategoryChange(value)}
							disabled={categoriesLoading}
							help={categoriesLoading ? 'Loading categories...' : 'Select a book category to filter by'}
						/>
						<TextControl
							label="Number of Books"
							type="number"
							value={limit}
							onChange={(value) => {
								const num = parseInt(value);
								if (!isNaN(num) && num > 0 && num <= 50) {
									setAttributes({ limit: num });
								}
							}}
							help="Maximum number of books to display (1-50)"
						/>
						<SelectControl
							label="Order By"
							value={orderby}
							options={[
								{ label: 'Title', value: 'title' },
								{ label: 'Date', value: 'date' },
								{ label: 'Modified', value: 'modified' },
								{ label: 'Menu Order', value: 'menu_order' }
							]}
							onChange={(value) => setAttributes({ orderby: value })}
						/>
						<SelectControl
							label="Order"
							value={order}
							options={[
								{ label: 'Ascending', value: 'ASC' },
								{ label: 'Descending', value: 'DESC' }
							]}
							onChange={(value) => setAttributes({ order: value })}
						/>
					</PanelBody>
					<PanelBody title="Display Options" initialOpen={false}>
						<ToggleControl
							label="Show Meta"
							checked={showMeta}
							onChange={(value) => setAttributes({ showMeta: value })}
							help="Display book metadata (author, ISBN, etc.)"
						/>
						<ToggleControl
							label="Show Excerpt"
							checked={showExcerpt}
							onChange={(value) => setAttributes({ showExcerpt: value })}
							help="Display book excerpt/description"
						/>
						<RangeControl
							label="Columns"
							value={columns}
							onChange={(value) => setAttributes({ columns: value })}
							min={1}
							max={6}
							help="Number of columns in the grid"
						/>
						<SelectControl
							label="Template"
							value={template}
							options={[
								{ label: 'Grid', value: 'grid' },
								{ label: 'List', value: 'list' }
							]}
							onChange={(value) => setAttributes({ template: value })}
						/>
					</PanelBody>
				</InspectorControls>
				<div {...blockProps}>
					<div className="ttbp-books-list total-books-list ttbp-books-grid" style={{
						margin: '20px 0',
						padding: 0
					}}>
						{booksLoading ? (
							<div style={{ padding: '40px', textAlign: 'center', color: '#666' }}>
								Loading books...
							</div>
						) : books.length === 0 ? (
							<div style={{ padding: '40px', textAlign: 'center', color: '#666' }}>
								No books found. {category && category.length > 0 && 'Try selecting a different category or clearing the filter.'}
							</div>
						) : (
							<>
								<style>{`
									.wp-block-ttbp-book-shelf .books-grid {
										display: grid !important;
										grid-template-columns: repeat(${columns}, 1fr) !important;
										gap: 18px !important;
										margin: 0 !important;
										padding: 0 !important;
										list-style: none !important;
										width: 100% !important;
									}
								`}</style>
								<div 
									className={`books-grid book-col-${columns}`} 
									style={{ 
										display: 'grid',
										gridTemplateColumns: `repeat(${columns}, 1fr)`,
										gap: '18px',
										margin: 0,
										padding: 0,
										listStyle: 'none',
										width: '100%'
									}}
								>
								{books.map((book) => (
									<div key={book.id} className="book-item">
										<div className="book-cover">
											{book.featured_image ? (
												<img 
													src={book.featured_image.url} 
													alt={book.title}
													style={{
														width: '100%',
														height: '220px',
														objectFit: 'cover'
													}}
												/>
											) : (
												<div className="book-cover-placeholder" style={{
													height: '220px',
													background: '#f0f0f0',
													display: 'flex',
													alignItems: 'center',
													justifyContent: 'center',
													color: '#999',
													fontSize: '48px'
												}}>
													📚
												</div>
											)}
											<div className="book-overlay">
												<div className="book-overlay-link">
													<div className="book-title">{book.title}</div>
													<div className="book-author">
														{book.authors && book.authors.length > 0 
															? book.authors.join(', ')
															: 'Unknown Author'
														}
													</div>
												</div>
											</div>
										</div>
									</div>
								))}
								</div>
							</>
						)}
					</div>
				</div>
			</>
		);
	},
	save: () => {
		// Dynamic block - no save needed
		return null;
	},
});

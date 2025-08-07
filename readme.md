# Total Book

A WordPress plugin for publishing books online with advanced e-reader functionality and chapter management.

## Features

- **Custom Post Types**: `book` and `chapter` with hierarchical relationships
- **Advanced E-Reader**: React-based reader with intelligent pagination
- **Chapter Management**: Drag-and-drop ordering with AJAX support
- **Multiple Templates**: Plain, reader, and blog display options
- **Book Metadata**: Author, ISBN, publication date, publisher, description
- **Category System**: Hierarchical book categories
- **Author Taxonomy**: Clickable author taxonomy with automatic migration
- **Shortcode System**: Flexible content embedding

## Installation

1. Upload to `/wp-content/plugins/total-book/`
2. Activate through WordPress admin
3. For development: `npm install && npm run dev`

## Usage

### Admin Interface

**Creating Books**
1. Go to **Books > Add New**
2. Fill in book details (title, authors, metadata)
3. Add chapters using the **Book Chapters** meta box
4. Set featured image and assign categories

**Author Management**
- Books can have multiple authors
- Authors are clickable taxonomy terms
- Manage under **Books > Authors**

**Chapter Management**
- Add, reorder, edit, and delete chapters
- Drag-and-drop reordering with AJAX

### Shortcodes

**Display a Single Book**
```
[total_book id="123" show_meta="true" show_toc="true" show_description="true"]
```

**Display Books List**
```
[total_books category="fiction" limit="10" orderby="title" order="ASC" columns="3"]
```

**Shortcode Parameters**

| Parameter | Description | Default |
|-----------|-------------|---------|
| `id` | Book post ID (required for single book) | - |
| `category` | Book category slug(s), comma-separated | - |
| `limit` | Number of books to display | 10 |
| `orderby` | Sort field (title, date, menu_order) | title |
| `order` | Sort direction (ASC, DESC) | ASC |
| `show_meta` | Display book metadata | true |
| `show_toc` | Display table of contents | true |
| `show_description` | Display book description | true |
| `columns` | Grid columns for list display | 3 |
| `template` | Display template (grid, list) | grid |

### Templates

- **Plain**: Simple HTML output
- **Reader**: Advanced React-based e-reader
- **Blog**: Traditional blog-style layout

## Technical Requirements

- WordPress 5.0+
- PHP 7.4+
- Node.js 18+ (development only)

## File Structure

```
total_book/
├── modules/          # PHP modules (book, chapter, settings, etc.)
├── src/              # React/TypeScript source
├── templates/        # PHP templates
├── CSS/              # Stylesheets
└── js/               # JavaScript files
```

## Development

```bash
# Install dependencies
npm install

# Development with watch mode
npm run dev

# Production build
npm run build
```

## Support

For issues and feature requests, please refer to the plugin documentation or contact support.
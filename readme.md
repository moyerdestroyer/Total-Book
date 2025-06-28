# Total Book

A comprehensive WordPress plugin for publishing books online with advanced e-reader functionality, chapter management, and multiple display templates.

## Features

### Core Functionality
- **Custom Post Types**: Dedicated `book` and `chapter` post types with hierarchical relationships
- **Advanced E-Reader**: React-based reader with intelligent pagination and responsive design
- **Chapter Management**: Drag-and-drop chapter ordering with AJAX support
- **Multiple Templates**: Plain, reader, and blog display options
- **Book Metadata**: Author, ISBN, publication date, publisher, description, dedication, acknowledgments
- **Category System**: Hierarchical book categories for organization
- **Author Taxonomy**: Clickable author taxonomy with automatic migration from legacy fields

### Technical Features
- **Smart Pagination**: Binary search algorithm for optimal word splitting across pages
- **Responsive Design**: Mobile-first approach with adaptive layouts
- **REST API Support**: Built-in WordPress REST API integration
- **Shortcode System**: Flexible content embedding with customizable parameters
- **Performance Optimized**: Efficient DOM manipulation and memory management

## Installation

### Prerequisites
- WordPress 5.0+
- PHP 7.4+
- Node.js 16+ (for development)

### Setup
1. Upload the plugin to `/wp-content/plugins/total-book/`
2. Activate the plugin through the WordPress admin


### E-reader Build Process (DEV ONLY)
```bash
npm install

# Development with watch mode
npm run dev

# Production build
npm run build
```

## Usage

### Admin Interface

#### Creating Books
1. Navigate to **Books > Add New**
2. Fill in book details:
   - Title and subtitle
   - Authors (required) - Select from existing authors or add new ones
   - ISBN, publication date, publisher
   - Description, dedication, acknowledgments
   - About the author
3. Add chapters using the **Book Chapters** meta box
4. Set featured image for book cover
5. Assign book categories

#### Author Management
- **Multiple Authors**: Books can have multiple authors using the enhanced dropdown
- **Author Taxonomy**: Authors are stored as clickable taxonomy terms
- **Automatic Migration**: Existing books with legacy author fields are automatically migrated
- **Author Pages**: Click on author names to view all books by that author
- **Admin Interface**: Manage authors under **Books > Authors** in the admin menu

#### Chapter Management
- **Add Chapters**: Click "Add Chapter" in the book edit screen
- **Reorder**: Drag and drop chapters to reorder
- **Edit**: Click chapter titles to edit content
- **Delete**: Remove chapters with confirmation

#### Settings
Navigate to **Books > Settings** to configure:
- **Template Selection**: Choose display template (plain, reader, blog)
- **Display Options**: Toggle metadata, table of contents, copyright notices

### Frontend Display

#### Templates
- **Plain**: Simple HTML output with basic styling
- **Reader**: Advanced React-based e-reader with pagination
- **Blog**: Traditional blog-style layout

#### Shortcodes

**Books List**
```php
[total_books category="fiction" limit="10" orderby="title" order="ASC" columns="3"]
```

**Single Book Display**
```php
[total_book id="123" show_meta="true" show_toc="true" show_description="true"]
```



**Shortcode Parameters**
- `id`: Book post ID (required for single book)
- `category`: Book category slug(s), comma-separated
- `limit`: Number of books to display (default: 10)
- `orderby`: Sort field (title, date, menu_order)
- `order`: Sort direction (ASC, DESC)
- `show_meta`: Display book metadata (true/false)
- `show_toc`: Display table of contents (true/false)
- `show_description`: Display book description (true/false)
- `columns`: Grid columns for list display (1-6)
- `template`: Display template (grid, list)

### E-Reader Features

#### Pagination Algorithm (DEV ONLY)
The e-reader uses an optimized binary search algorithm for intelligent content splitting:

```typescript
// Binary search for optimal word count per page
let left = 0;
let right = words.length;
let bestFit = 0;

while (left <= right) {
  const mid = Math.floor((left + right) / 2);
  const testWords = words.slice(0, mid);
  
  if (height <= this.pageHeight) {
    bestFit = mid;
    left = mid + 1;
  } else {
    right = mid - 1;
  }
}
```

#### Responsive Design
- Adaptive page height calculation
- Mobile-optimized touch controls
- Flexible typography scaling
- Cross-browser compatibility

## Technical Architecture

### File Structure
```
total_book/
├── CSS/                    # Stylesheets
├── js/                     # JavaScript files
├── modules/                # PHP modules
│   ├── book.php           # Book post type and management
│   ├── chapter.php        # Chapter functionality
│   ├── settings.php       # Plugin settings
│   ├── rest_apis.php      # REST API endpoints
│   ├── shortcodes.php     # Shortcode system
│   └── blog.php           # Blog template
├── src/                    # React/TypeScript source
│   ├── Components/        # React components
│   ├── styles/            # SCSS styles
│   └── utils/             # Utility functions
├── templates/              # PHP templates
└── dist/                   # Built assets
```

### Key Classes

#### EReaderPaginator
Advanced pagination with intelligent content splitting:
- Binary search optimization
- Paragraph-aware splitting
- Memory-efficient DOM manipulation
- Error handling and safeguards

#### TB_Book
Core book management:
- Custom post type registration
- Meta box handling
- AJAX chapter management
- Data validation and sanitization

#### TB_Settings
Configuration management:
- Template selection
- Display options
- Settings persistence
- Admin interface

### API Endpoints

#### REST API
- `GET /wp-json/wp/v2/book` - List books
- `GET /wp-json/wp/v2/book/{id}` - Get book details
- `GET /wp-json/wp/v2/chapter` - List chapters
- `GET /wp-json/wp/v2/chapter/{id}` - Get chapter content

#### AJAX Actions
- `add_chapter` - Create new chapter
- `delete_chapter` - Remove chapter
- `update_chapter_order` - Reorder chapters

## Customization

### Templates
Create custom templates by adding PHP files to the `templates/` directory:
```php
<?php
// Custom template: templates/custom.php
get_header();
// Your custom book display logic
get_footer();
```

### Hooks and Filters
```php
// Modify book query
add_filter('total_book_query_args', function($args) {
    // Custom query modifications
    return $args;
});

// Customize book display
add_action('total_book_before_content', function($book_id) {
    // Custom content before book
});
```

Well, that's all I can think of for now. Enjoy the plugin!
=== The Total Book Project ===
Contributors: ryanmoyer
Tags: books, library
Tested up to: 6.8
Stable tag: 1.0
Requires PHP: 7.4
License: GPLv2 or later

Host complete books on WordPress with professional e-reader functionality, chapter management, and multiple display templates.

== Description ==

Total Book transforms WordPress into a digital library platform. Features include:

**Core Features:**
* Complete book management with chapters, front/back matter
* React-based e-reader with pagination, themes, and navigation
* Multiple display templates (reader, blog, plain)

**E-Reader Features:**
* Dark/light theme switching
* Adjustable font sizes
* Table of contents navigation
* Progress tracking

**Integration:**
* Shortcodes for easy display
* WordPress admin interface

**GitHub Repository:** [https://github.com/moyerdestroyer/Total-Book](https://github.com/moyerdestroyer/Total-Book)

== Installation ==

1. Upload `the-total-book-project` to `/wp-content/plugins/`
2. Activate via WordPress admin
3. Go to 'Books' menu to create content
4. Configure settings under 'Books > Settings'

**E-READER DEVELOPMENT**

The e-reader component is built with React and TypeScript. To modify or develop the e-reader:

1. Install dependencies: `npm install`
2. Build/Webpack: `npm run build`

**Requirements for e-reader development:**
* Node.js 18+ and npm

The e-reader source is in `src/` directory. Build output goes to `dist/` directory.

== Shortcode Options ==

### Single Book
`[ttbp_book]`

**Parameters:**
* `id` (required): Book post ID
* `show_meta` (optional): Show metadata (true/false, default: true)
* `show_toc` (optional): Show table of contents (true/false, default: true)
* `show_description` (optional): Show description (true/false, default: true)

### Book List
`[ttbp_books]`

**Parameters:**
* `category` (optional): Filter by category slug(s)
* `limit` (optional): Number of books (default: 10)
* `orderby` (optional): Sort by (title, date, menu_order, default: title)
* `order` (optional): Sort order (ASC/DESC, default: ASC)
* `show_meta` (optional): Show metadata (true/false, default: true)
* `show_excerpt` (optional): Show excerpt (true/false, default: true)
* `columns` (optional): Grid columns (1-6, default: 3)

### Examples
```
[ttbp_book id="123" show_meta="true"]
[ttbp_books category="fiction" limit="6" columns="2"]
[ttbp_books orderby="date" order="DESC" limit="5"]
```

== Changelog ==

= 1.0 =
* Initial release
* Complete book management system
* React-based e-reader
* Multiple display templates
* Chapter organization
* REST API
* Mobile-responsive design
* Dark/light themes
* Font size controls
* Table of contents
* Progress tracking
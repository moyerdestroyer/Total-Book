<?php

if (!defined('ABSPATH')) {
    exit;
}

Class TTBP_Import {
    private $upload_dir;
    private $temp_dir;
    
    public function __construct() {
        // Import submenu item
        add_action('admin_menu', array($this, 'ttbp_add_import_submenu'));
        add_action('admin_enqueue_scripts', array($this, 'ttbp_enqueue_import_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_ttbp_upload_epub', array($this, 'ttbp_ajax_upload_epub'));
        add_action('wp_ajax_ttbp_extract_metadata', array($this, 'ttbp_ajax_extract_metadata'));
        add_action('wp_ajax_ttbp_create_book_from_import', array($this, 'ttbp_ajax_create_book_from_import'));
        add_action('wp_ajax_ttbp_get_cover_image', array($this, 'ttbp_ajax_get_cover_image'));
        add_action('wp_ajax_ttbp_update_import_metadata', array($this, 'ttbp_ajax_update_import_metadata'));
        
        // Set up upload directories
        $upload_dir = wp_upload_dir();
        $this->upload_dir = $upload_dir['basedir'] . '/ttbp-imports';
        $this->temp_dir = $this->upload_dir . '/temp';
        
        // Create directories if they don't exist
        if (!file_exists($this->upload_dir)) {
            wp_mkdir_p($this->upload_dir);
        }
        if (!file_exists($this->temp_dir)) {
            wp_mkdir_p($this->temp_dir);
        }
    }
    
    public function ttbp_add_import_submenu() {
        add_submenu_page(
            'edit.php?post_type=ttbp-book',
            __('Import', 'the-total-book-project'),
            __('Import', 'the-total-book-project'),
            'manage_options',
            'ttbp-import',
            array($this, 'ttbp_render_import_page')
        );
    }
    
    public function ttbp_enqueue_import_scripts($hook) {
        // Only load on import page
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'ttbp-book_page_ttbp-import') {
            return;
        }
        
        wp_enqueue_style(
            'ttbp-import',
            plugin_dir_url(dirname(__FILE__)) . 'CSS/book-import.css',
            array(),
            '1.0.0'
        );
        
        wp_enqueue_script(
            'ttbp-import',
            plugin_dir_url(dirname(__FILE__)) . 'js/book-import.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        wp_localize_script('ttbp-import', 'ttbpImport', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ttbp_import_nonce'),
            'messages' => array(
                'uploading' => __('Uploading...', 'the-total-book-project'),
                'extracting' => __('Extracting metadata...', 'the-total-book-project'),
                'creating' => __('Creating book...', 'the-total-book-project'),
                'error' => __('An error occurred. Please try again.', 'the-total-book-project'),
                'invalidFile' => __('Please upload a valid EPUB file.', 'the-total-book-project'),
                'fileTooLarge' => __('File is too large. Maximum size: %s', 'the-total-book-project'),
            )
        ));
    }
    
    public function ttbp_render_import_page() {
        ?>
        <div class="wrap ttbp-import-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="ttbp-import-container">
                <!-- Upload Area -->
                <div id="ttbp-upload-area" class="ttbp-upload-area">
                    <div class="ttbp-upload-content">
                        <span class="dashicons dashicons-upload"></span>
                        <h2><?php esc_html_e('Upload EPUB File', 'the-total-book-project'); ?></h2>
                        <p><?php esc_html_e('Drag and drop your EPUB file here, or click to browse', 'the-total-book-project'); ?></p>
                        <input type="file" id="ttbp-file-input" accept=".epub" style="display: none;">
                        <button type="button" class="button button-primary" id="ttbp-browse-btn">
                            <?php esc_html_e('Browse Files', 'the-total-book-project'); ?>
                        </button>
                    </div>
                    <div class="ttbp-upload-progress" style="display: none;">
                        <div class="ttbp-progress-bar">
                            <div class="ttbp-progress-fill"></div>
                        </div>
                        <p class="ttbp-progress-text"></p>
                    </div>
                </div>
                
                <!-- Metadata Preview -->
                <div id="ttbp-metadata-preview" class="ttbp-metadata-preview" style="display: none;">
                    <h2><?php esc_html_e('Book Preview', 'the-total-book-project'); ?></h2>
                    <div class="ttbp-preview-content">
                        <div class="ttbp-preview-cover">
                            <img id="ttbp-preview-cover-img" src="" alt="" style="display: none;">
                            <div class="ttbp-no-cover"><?php esc_html_e('No cover image', 'the-total-book-project'); ?></div>
                        </div>
                        <div class="ttbp-preview-details">
                            <div class="ttbp-preview-field">
                                <label><?php esc_html_e('Title', 'the-total-book-project'); ?></label>
                                <div id="ttbp-preview-title" class="ttbp-preview-value"></div>
                            </div>
                            <div class="ttbp-preview-field">
                                <label><?php esc_html_e('Subtitle', 'the-total-book-project'); ?></label>
                                <div id="ttbp-preview-subtitle" class="ttbp-preview-value"></div>
                            </div>
                            <div class="ttbp-preview-field">
                                <label><?php esc_html_e('Authors', 'the-total-book-project'); ?></label>
                                <div id="ttbp-preview-authors" class="ttbp-preview-value"></div>
                            </div>
                            <div class="ttbp-preview-field">
                                <label><?php esc_html_e('Publisher', 'the-total-book-project'); ?></label>
                                <div id="ttbp-preview-publisher" class="ttbp-preview-value"></div>
                            </div>
                            <div class="ttbp-preview-field">
                                <label><?php esc_html_e('Publication Date', 'the-total-book-project'); ?></label>
                                <div id="ttbp-preview-date" class="ttbp-preview-value"></div>
                            </div>
                            <div class="ttbp-preview-field">
                                <label><?php esc_html_e('ISBN', 'the-total-book-project'); ?></label>
                                <div id="ttbp-preview-isbn" class="ttbp-preview-value"></div>
                            </div>
                            <div class="ttbp-preview-field">
                                <label><?php esc_html_e('Description', 'the-total-book-project'); ?></label>
                                <div id="ttbp-preview-description" class="ttbp-preview-value"></div>
                            </div>
                        </div>
                    </div>
                    <div class="ttbp-preview-chapters" id="ttbp-preview-chapters" style="display: none;">
                        <h3><?php esc_html_e('Chapters', 'the-total-book-project'); ?></h3>
                        <div id="ttbp-preview-chapters-list" class="ttbp-chapters-list"></div>
                    </div>
                    <div class="ttbp-preview-actions">
                        <button type="button" class="button" id="ttbp-cancel-import">
                            <?php esc_html_e('Cancel', 'the-total-book-project'); ?>
                        </button>
                        <button type="button" class="button button-primary button-large" id="ttbp-create-book">
                            <?php esc_html_e('Create Book', 'the-total-book-project'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle EPUB file upload via AJAX
     */
    public function ttbp_ajax_upload_epub() {
        check_ajax_referer('ttbp_import_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'the-total-book-project')));
        }
        
        if (!isset($_FILES['file'])) {
            wp_send_json_error(array('message' => __('No file uploaded', 'the-total-book-project')));
        }
        
        $file = $_FILES['file'];
        
        // Check file type
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file_ext !== 'epub') {
            wp_send_json_error(array('message' => __('Invalid file type. Please upload an EPUB file.', 'the-total-book-project')));
        }
        
        // Check file size (max 50MB)
        $max_size = 50 * 1024 * 1024; // 50MB
        if ($file['size'] > $max_size) {
            wp_send_json_error(array('message' => __('File is too large. Maximum size is 50MB.', 'the-total-book-project')));
        }
        
        // Generate unique filename
        $filename = sanitize_file_name($file['name']);
        $unique_filename = wp_unique_filename($this->temp_dir, $filename);
        $file_path = $this->temp_dir . '/' . $unique_filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            wp_send_json_error(array('message' => __('Failed to save uploaded file.', 'the-total-book-project')));
        }
        
        // Store file info in transient (expires in 1 hour)
        $file_id = md5($unique_filename . time());
        set_transient('ttbp_import_file_' . $file_id, array(
            'path' => $file_path,
            'original_name' => $filename,
            'upload_time' => time()
        ), HOUR_IN_SECONDS);
        
        wp_send_json_success(array(
            'file_id' => $file_id,
            'filename' => $filename
        ));
    }
    
    /**
     * Extract metadata from EPUB file
     */
    public function ttbp_ajax_extract_metadata() {
        check_ajax_referer('ttbp_import_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'the-total-book-project')));
        }
        
        if (!isset($_POST['file_id'])) {
            wp_send_json_error(array('message' => __('File ID is required', 'the-total-book-project')));
        }
        
        $file_id = sanitize_text_field(wp_unslash($_POST['file_id']));
        $file_info = get_transient('ttbp_import_file_' . $file_id);
        
        if (!$file_info || !file_exists($file_info['path'])) {
            wp_send_json_error(array('message' => __('File not found', 'the-total-book-project')));
        }
        
        $metadata = $this->ttbp_extract_epub_metadata($file_info['path'], $file_id);
        
        if (is_wp_error($metadata)) {
            wp_send_json_error(array('message' => $metadata->get_error_message()));
        }
        
        // Store metadata in transient
        set_transient('ttbp_import_metadata_' . $file_id, $metadata, HOUR_IN_SECONDS);
        
        wp_send_json_success($metadata);
    }
    
    /**
     * Extract metadata from EPUB file
     */
    private function ttbp_extract_epub_metadata($epub_path, $file_id) {
        if (!class_exists('ZipArchive')) {
            return new WP_Error('no_zip', __('PHP ZipArchive extension is required to import EPUB files.', 'the-total-book-project'));
        }
    
        $zip = new ZipArchive();
        if ($zip->open($epub_path) !== true) {
            return new WP_Error('invalid_epub', __('Invalid EPUB file. Could not open archive.', 'the-total-book-project'));
        }
    
        $metadata = array(
            'title'            => '',
            'subtitle'         => '',
            'authors'          => array(),
            'publisher'        => '',
            'publication_date' => '',
            'isbn'             => '',
            'description'      => '',
            'dedication'       => '',
            'acknowledgments'  => '',
            'about_author'     => '',
            'cover_path'       => '',
            'cover_image'      => '', // base64 data URL
            'chapters'         => array()
        );
    
        // === Find OPF file ===
        $container_xml = $zip->getFromName('META-INF/container.xml');
        if (!$container_xml) {
            $zip->close();
            return new WP_Error('invalid_epub', __('Invalid EPUB file. Missing container.xml.', 'the-total-book-project'));
        }
    
        $container = simplexml_load_string($container_xml);
        if (!$container) {
            $zip->close();
            return new WP_Error('invalid_epub', __('Invalid EPUB file. Could not parse container.xml.', 'the-total-book-project'));
        }
    
        $opf_path = '';
        foreach ($container->rootfiles->rootfile as $rootfile) {
            $opf_path = (string)$rootfile['full-path'];
            break;
        }
    
        if (empty($opf_path)) {
            $zip->close();
            return new WP_Error('invalid_epub', __('Invalid EPUB file. Could not find OPF file.', 'the-total-book-project'));
        }
    
        $opf_dir = dirname($opf_path);
        if ($opf_dir === '.') $opf_dir = '';
        else $opf_dir .= '/';
    
        $opf_content = $zip->getFromName($opf_path);
        if (!$opf_content) {
            $zip->close();
            return new WP_Error('invalid_epub', __('Invalid EPUB file. Could not read OPF file.', 'the-total-book-project'));
        }
    
        libxml_use_internal_errors(true);
        $opf = simplexml_load_string($opf_content);
        if (!$opf) {
            $zip->close();
            return new WP_Error('invalid_epub', __('Invalid EPUB file. Could not parse OPF file.', 'the-total-book-project'));
        }
    
        $opf->registerXPathNamespace('dc', 'http://purl.org/dc/elements/1.1/');
        $opf->registerXPathNamespace('opf', 'http://www.idpf.org/2007/opf');
    
        // === Basic Dublin Core Metadata ===
        $metadata['title'] = (string)$opf->metadata->children('dc', true)->title;
    
        $alt_titles = $opf->metadata->children('dc', true)->title;
        foreach ($alt_titles as $title) {
            if ((string)$title->attributes()->{'opf:title-type'} === 'subtitle') {
                $metadata['subtitle'] = (string)$title;
                break;
            }
        }
    
        foreach ($opf->metadata->children('dc', true)->creator as $creator) {
            $name = trim((string)$creator);
            if ($name) $metadata['authors'][] = $name;
        }
    
        $metadata['publisher'] = (string)$opf->metadata->children('dc', true)->publisher;
    
        $date = (string)$opf->metadata->children('dc', true)->date;
        if ($date) {
            $parsed = strtotime($date);
            $metadata['publication_date'] = $parsed ? date('Y-m-d', $parsed) : $date;
        }
    
        // ISBN
        foreach ($opf->metadata->children('dc', true)->identifier as $identifier) {
            $value = (string)$identifier;
            $scheme = (string)$identifier['scheme'];
            if (stripos($scheme, 'isbn') !== false || stripos($value, 'isbn') !== false) {
                preg_match('/\b(?:97[89])?\d{9}(\d|X)\b/', $value, $m);
                $metadata['isbn'] = $m ? str_replace('-', '', $m[0]) : preg_replace('/\D/', '', $value);
                break;
            }
        }
    
        // Description
        $desc = $opf->metadata->children('dc', true)->description;
        if ($desc) {
            $metadata['description'] = trim(implode(' ', array_map('strval', iterator_to_array($desc))));
        }
    
        // === COVER EXTRACTION – THE GOOD STUFF ===
        $manifest_items = $opf->manifest->item;
        $cover_id = $cover_href = '';
    
        // Helper: resolve paths with ../ support
        $resolve = function($href) use ($opf_dir) {
            $path = $opf_dir . $href;
            $parts = explode('/', $path);
            $stack = [];
            foreach ($parts as $part) {
                if ($part === '' || $part === '.') continue;
                if ($part === '..') {
                    array_pop($stack);
                } else {
                    $stack[] = $part;
                }
            }
            return implode('/', $stack);
        };
    
        // Method 0: EPUB 3 – properties="cover-image" (MOST COMMON NOW)
        foreach ($manifest_items as $item) {
            $props = (string)$item['properties'];
            if (strpos($props, 'cover-image') !== false) {
                $cover_id = (string)$item['id'];
                $cover_href = (string)$item['href'];
                break;
            }
        }
    
        // Method 1: <meta name="cover" content="some-id">
        if (!$cover_id) {
            foreach ($opf->metadata->meta as $meta) {
                if ((string)$meta['name'] === 'cover') {
                    $cover_id = (string)$meta['content'];
                    break;
                }
            }
        }
    
        // Method 2: <guide><reference type="cover">
        if (!$cover_id && isset($opf->guide)) {
            foreach ($opf->guide->reference as $ref) {
                if ((string)$ref['type'] === 'cover') {
                    $cover_href = (string)$ref['href'];
                    foreach ($manifest_items as $item) {
                        if ((string)$item['href'] === $cover_href) {
                            $cover_id = (string)$item['id'];
                            break 2;
                        }
                    }
                }
            }
        }
    
        // Method 3: item ID contains "cover"
        if (!$cover_id) {
            foreach ($manifest_items as $item) {
                $id = (string)$item['id'];
                $mt  = (string)$item['media-type'];
                if (stripos($id, 'cover') !== false && stripos($mt, 'image') !== false) {
                    $cover_id = $id;
                    break;
                }
            }
        }
    
        // Method 4: common filenames
        if (!$cover_id) {
            $common = ['cover.jpg', 'cover.jpeg', 'cover.png', 'cover.gif', 'cover.webp'];
            foreach ($common as $name) {
                foreach ($manifest_items as $item) {
                    if (strtolower(basename((string)$item['href'])) === $name) {
                        $cover_id = (string)$item['id'];
                        $cover_href = (string)$item['href'];
                        break 2;
                    }
                }
            }
        }
    
        // Method 5: spine item with linear="no" (Apple Books, etc.)
        if (!$cover_id && isset($opf->spine->itemref)) {
            foreach ($opf->spine->itemref as $itemref) {
                if ((string)$itemref['linear'] === 'no') {
                    $idref = (string)$itemref['idref'];
                    foreach ($manifest_items as $item) {
                        if ((string)$item['id'] === $idref) {
                            $cover_id = $idref;
                            break 2;
                        }
                    }
                }
            }
        }
    
        // === Extract actual image ===
        if ($cover_id) {
            foreach ($manifest_items as $item) {
                if ((string)$item['id'] !== $cover_id) continue;
    
                $item_href = (string)$item['href'];
                $media_type = (string)$item['media-type'];
    
                $cover_data = null;
                $mime = $media_type;
    
                if (stripos($media_type, 'html') !== false || stripos($media_type, 'xhtml') !== false) {
                    // Cover is an HTML/XHTML page → extract <img>
                    $html_path = $resolve($item_href);
                    $html = $zip->getFromName($html_path);
    
                    if ($html && preg_match('/<img[^>]+src=["\']([^"\'>\s]+)["\']/i', $html, $m)) {
                        $img_src = html_entity_decode($m[1], ENT_QUOTES);
                        $img_path = $resolve(dirname($item_href) . '/' . $img_src);
                        $cover_data = $zip->getFromName($img_path);
    
                        if ($cover_data) {
                            $ext = strtolower(pathinfo($img_src, PATHINFO_EXTENSION)) ?: 'jpg';
                            $mime = $this->ttbp_get_image_mime_type($ext);
                        }
                    }
                } else {
                    // Direct image file
                    $image_path = $resolve($item_href);
                    $cover_data = $zip->getFromName($image_path);
    
                    if (!$cover_data && $cover_href) {
                        $image_path = $resolve($cover_href);
                        $cover_data = $zip->getFromName($image_path);
                    }
    
                    if ($cover_data && empty($mime)) {
                        $ext = strtolower(pathinfo($image_path, PATHINFO_EXTENSION));
                        $mime = $this->ttbp_get_image_mime_type($ext);
                    }
                }
    
                if ($cover_data) {
                    $ext = $this->ttbp_get_extension_from_mime($mime) ?: 'jpg';
                    $filename = 'cover_' . $file_id . '.' . $ext;
                    $file_path = $this->temp_dir . '/' . $filename;
    
                    file_put_contents($file_path, $cover_data);
    
                    $metadata['cover_path']  = $file_path;
                    $metadata['cover_image'] = 'data:' . $mime . ';base64,' . base64_encode($cover_data);
                }
    
                break;
            }
        }
    
        // === Extract chapters ===
        $metadata['chapters'] = $this->ttbp_extract_chapters($zip, $opf, $opf_dir, $opf_path);
    
        $zip->close();
        return $metadata;
    }
    
    /**
     * Create book from imported metadata
     */
    public function ttbp_ajax_create_book_from_import() {
        check_ajax_referer('ttbp_import_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'the-total-book-project')));
        }
        
        if (!isset($_POST['file_id'])) {
            wp_send_json_error(array('message' => __('File ID is required', 'the-total-book-project')));
        }
        
        $file_id = sanitize_text_field(wp_unslash($_POST['file_id']));
        $metadata = get_transient('ttbp_import_metadata_' . $file_id);
        
        if (!$metadata) {
            wp_send_json_error(array('message' => __('Metadata not found. Please re-upload the file.', 'the-total-book-project')));
        }
        
        // Create book post
        $book_data = array(
            'post_title' => !empty($metadata['title']) ? sanitize_text_field($metadata['title']) : __('Imported Book', 'the-total-book-project'),
            'post_type' => 'ttbp-book',
            'post_status' => 'draft',
            'post_content' => ''
        );
        
        $book_id = wp_insert_post($book_data);
        
        if (is_wp_error($book_id)) {
            wp_send_json_error(array('message' => $book_id->get_error_message()));
        }
        
        // Save metadata
        if (!empty($metadata['subtitle'])) {
            update_post_meta($book_id, '_book_subtitle', sanitize_text_field($metadata['subtitle']));
        }
        
        if (!empty($metadata['authors'])) {
            $author_names = array_map('sanitize_text_field', $metadata['authors']);
            foreach ($author_names as $author_name) {
                $term = term_exists($author_name, 'ttbp_book_author');
                if (!$term) {
                    wp_insert_term($author_name, 'ttbp_book_author');
                }
            }
            wp_set_object_terms($book_id, $author_names, 'ttbp_book_author');
        }
        
        if (!empty($metadata['publisher'])) {
            update_post_meta($book_id, '_book_publisher', sanitize_text_field($metadata['publisher']));
        }
        
        if (!empty($metadata['publication_date'])) {
            update_post_meta($book_id, '_book_publication_date', sanitize_text_field($metadata['publication_date']));
        }
        
        if (!empty($metadata['isbn'])) {
            update_post_meta($book_id, '_book_isbn', sanitize_text_field($metadata['isbn']));
        }
        
        if (!empty($metadata['description'])) {
            update_post_meta($book_id, '_book_description', sanitize_textarea_field($metadata['description']));
        }
        
        if (!empty($metadata['dedication'])) {
            update_post_meta($book_id, '_book_dedication', sanitize_textarea_field($metadata['dedication']));
        }
        
        if (!empty($metadata['acknowledgments'])) {
            update_post_meta($book_id, '_book_acknowledgments', sanitize_textarea_field($metadata['acknowledgments']));
        }
        
        if (!empty($metadata['about_author'])) {
            update_post_meta($book_id, '_book_about_author', sanitize_textarea_field($metadata['about_author']));
        }
        
        // Handle cover image
        if (!empty($metadata['cover_path']) && file_exists($metadata['cover_path'])) {
            $this->ttbp_import_cover_image($book_id, $metadata['cover_path']);
        }
        
        // Check if we should parse as blocks
        $settings = new TTBP_Settings();
        $parse_as_blocks = $settings->get_option('parse_import_as_blocks', false);
        
        // Create chapters
        if (!empty($metadata['chapters']) && is_array($metadata['chapters'])) {
            foreach ($metadata['chapters'] as $chapter_data) {
                // Convert content to blocks if setting is enabled
                $chapter_content = $chapter_data['content'];
                if ($parse_as_blocks) {
                    $chapter_content = $this->ttbp_convert_html_to_blocks($chapter_content);
                } else {
                    $chapter_content = wp_kses_post($chapter_content);
                }
                
                $chapter_id = wp_insert_post(array(
                    'post_title' => !empty($chapter_data['title']) ? sanitize_text_field($chapter_data['title']) : __('Chapter', 'the-total-book-project'),
                    'post_content' => $chapter_content,
                    'post_type' => 'ttbp_chapter',
                    'post_parent' => $book_id,
                    'post_status' => 'publish',
                    'menu_order' => isset($chapter_data['order']) ? intval($chapter_data['order']) : 0
                ));
                
                // Note: Chapter creation errors are logged but don't stop the import
                if (is_wp_error($chapter_id)) {
                    error_log('TTBP Import: Failed to create chapter: ' . $chapter_id->get_error_message());
                }
            }
        }
        
        // Clean up temp files
        $file_info = get_transient('ttbp_import_file_' . $file_id);
        if ($file_info && file_exists($file_info['path'])) {
            @unlink($file_info['path']);
        }
        if (!empty($metadata['cover_path']) && file_exists($metadata['cover_path'])) {
            @unlink($metadata['cover_path']);
        }
        
        // Delete transients
        delete_transient('ttbp_import_file_' . $file_id);
        delete_transient('ttbp_import_metadata_' . $file_id);
        
        wp_send_json_success(array(
            'book_id' => $book_id,
            'edit_url' => get_edit_post_link($book_id, '')
        ));
    }
    
    /**
     * Extract chapters from EPUB
     */
    private function ttbp_extract_chapters($zip, $opf, $opf_dir, $opf_path) {
        $chapters = array();
        
        // Get spine (reading order)
        $spine_items = $opf->spine->itemref;
        if (!$spine_items || count($spine_items) === 0) {
            return $chapters;
        }
        
        // Build manifest lookup
        $manifest_lookup = array();
        $manifest_items = $opf->manifest->item;
        foreach ($manifest_items as $item) {
            $id = (string)$item['id'];
            $href = (string)$item['href'];
            $media_type = (string)$item['media-type'];
            $manifest_lookup[$id] = array(
                'href' => $href,
                'media_type' => $media_type
            );
        }
        
        // Try to get chapter titles from NCX (EPUB 2) or Nav (EPUB 3)
        $chapter_titles = $this->ttbp_extract_chapter_titles($zip, $opf, $opf_dir, $manifest_lookup);
        
        // Process spine items in order
        $chapter_index = 0;
        foreach ($spine_items as $itemref) {
            $idref = (string)$itemref['idref'];
            
            // Skip if not in manifest
            if (!isset($manifest_lookup[$idref])) {
                continue;
            }
            
            $item = $manifest_lookup[$idref];
            $href = $item['href'];
            $media_type = $item['media_type'];
            
            // Only process HTML/XHTML content
            if (stripos($media_type, 'html') === false && stripos($media_type, 'xhtml') === false) {
                continue;
            }
            
            // Skip cover, title page, etc. (usually first items)
            $file_name = basename($href);
            if (stripos($file_name, 'cover') !== false || 
                stripos($file_name, 'title') !== false ||
                stripos($file_name, 'copyright') !== false ||
                stripos($file_name, 'dedication') !== false ||
                stripos($file_name, 'toc') !== false) {
                continue;
            }
            
            // Get chapter content
            $chapter_path = $opf_dir . $href;
            $chapter_content = $zip->getFromName($chapter_path);
            
            if ($chapter_content) {
                // Get chapter title
                $chapter_title = '';
                if (isset($chapter_titles[$idref])) {
                    $chapter_title = $chapter_titles[$idref];
                } else {
                    // Try to extract from HTML
                    if (preg_match('/<h1[^>]*>(.*?)<\/h1>/is', $chapter_content, $matches)) {
                        $chapter_title = strip_tags($matches[1]);
                    } elseif (preg_match('/<title[^>]*>(.*?)<\/title>/is', $chapter_content, $matches)) {
                        $chapter_title = strip_tags($matches[1]);
                    } else {
                        $chapter_title = sprintf(__('Chapter %d', 'the-total-book-project'), $chapter_index + 1);
                    }
                }
                
                // Clean up HTML content
                $cleaned_content = $this->ttbp_clean_chapter_html($chapter_content, $opf_dir, $href, $zip);
                
                $chapters[] = array(
                    'title' => trim($chapter_title),
                    'content' => $cleaned_content,
                    'order' => $chapter_index
                );
                
                $chapter_index++;
            }
        }
        
        return $chapters;
    }
    
    /**
     * Extract chapter titles from NCX or Nav document
     */
    private function ttbp_extract_chapter_titles($zip, $opf, $opf_dir, $manifest_lookup) {
        $titles = array();
        
        // Try EPUB 3 Nav document first
        $nav_items = $opf->manifest->item;
        foreach ($nav_items as $item) {
            $properties = (string)$item['properties'];
            if (stripos($properties, 'nav') !== false) {
                $nav_href = (string)$item['href'];
                $nav_path = $opf_dir . $nav_href;
                $nav_content = $zip->getFromName($nav_path);
                
                if ($nav_content) {
                    // Parse Nav document
                    libxml_use_internal_errors(true);
                    $nav_xml = simplexml_load_string($nav_content);
                    if ($nav_xml) {
                        $nav_xml->registerXPathNamespace('html', 'http://www.w3.org/1999/xhtml');
                        $nav_links = $nav_xml->xpath('//html:a[@href]');
                        
                        foreach ($nav_links as $link) {
                            $href = (string)$link['href'];
                            // Resolve relative path
                            $full_href = $opf_dir . dirname($nav_href) . '/' . $href;
                            $full_href = str_replace('//', '/', $full_href);
                            
                            // Find manifest item that matches
                            foreach ($manifest_lookup as $id => $item_data) {
                                $item_path = $opf_dir . $item_data['href'];
                                if ($item_path === $full_href || basename($item_path) === basename($full_href)) {
                                    $title = trim((string)$link);
                                    if (!empty($title)) {
                                        $titles[$id] = $title;
                                    }
                                    break;
                                }
                            }
                        }
                    }
                }
                break;
            }
        }
        
        // If no Nav document, try NCX (EPUB 2)
        if (empty($titles)) {
            $ncx_items = $opf->manifest->item;
            foreach ($ncx_items as $item) {
                $media_type = (string)$item['media-type'];
                if (stripos($media_type, 'ncx') !== false || stripos((string)$item['id'], 'ncx') !== false) {
                    $ncx_href = (string)$item['href'];
                    $ncx_path = $opf_dir . $ncx_href;
                    $ncx_content = $zip->getFromName($ncx_path);
                    
                    if ($ncx_content) {
                        libxml_use_internal_errors(true);
                        $ncx = simplexml_load_string($ncx_content);
                        if ($ncx) {
                            $ncx->registerXPathNamespace('ncx', 'http://www.daisy.org/z3986/2005/ncx/');
                            $nav_points = $ncx->xpath('//ncx:navPoint');
                            
                            foreach ($nav_points as $nav_point) {
                                $content = $nav_point->content;
                                $src = (string)$content['src'];
                                // Remove fragment identifier
                                $src = preg_replace('/#.*$/', '', $src);
                                
                                // Find manifest item
                                foreach ($manifest_lookup as $id => $item_data) {
                                    $item_href = $item_data['href'];
                                    if (basename($item_href) === basename($src) || $item_href === $src) {
                                        $title = trim((string)$nav_point->navLabel->text);
                                        if (!empty($title)) {
                                            $titles[$id] = $title;
                                        }
                                        break;
                                    }
                                }
                            }
                        }
                    }
                    break;
                }
            }
        }
        
        return $titles;
    }
    
    /**
     * Clean up chapter HTML content
     */
    private function ttbp_clean_chapter_html($html, $opf_dir, $chapter_href, $zip) {
        // Check if DOMDocument is available
        if (!class_exists('DOMDocument')) {
            // Fallback: basic regex cleanup
            $html = preg_replace('/^<\?xml[^>]*\?>/i', '', $html);
            $html = preg_replace('/<html[^>]*>/i', '', $html);
            $html = preg_replace('/<\/html>/i', '', $html);
            $html = preg_replace('/<head>.*?<\/head>/is', '', $html);
            $html = preg_replace('/<body[^>]*>/i', '', $html);
            $html = preg_replace('/<\/body>/i', '', $html);
            $html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
            $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
            return trim($html);
        }
        
        // Load HTML with proper encoding handling
        libxml_use_internal_errors(true);
        $dom = new DOMDocument('1.0', 'UTF-8');
        
        // Try to load with UTF-8 encoding
        $html_utf8 = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html_utf8);
        
        // Remove script and style tags
        $xpath = new DOMXPath($dom);
        $scripts = $xpath->query('//script | //style');
        foreach ($scripts as $script) {
            if ($script->parentNode) {
                $script->parentNode->removeChild($script);
            }
        }
        
        // Get body content
        $body = $dom->getElementsByTagName('body')->item(0);
        if ($body) {
            $inner_html = '';
            foreach ($body->childNodes as $child) {
                $inner_html .= $dom->saveHTML($child);
            }
            $html = $inner_html;
        } else {
            // Fallback: strip XML declaration and basic cleanup
            $html = preg_replace('/^<\?xml[^>]*\?>/i', '', $html);
            $html = preg_replace('/<html[^>]*>/i', '', $html);
            $html = preg_replace('/<\/html>/i', '', $html);
            $html = preg_replace('/<head>.*?<\/head>/is', '', $html);
            $html = preg_replace('/<body[^>]*>/i', '', $html);
            $html = preg_replace('/<\/body>/i', '', $html);
            $html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
            $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);
        }
        
        // Basic cleanup - preserve line breaks for readability
        $html = trim($html);
        
        return $html;
    }
    
    /**
     * Get image MIME type from extension
     */
    private function ttbp_get_image_mime_type($ext) {
        $mime_types = array(
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp'
        );
        return isset($mime_types[strtolower($ext)]) ? $mime_types[strtolower($ext)] : 'image/jpeg';
    }
    
    /**
     * Get file extension from MIME type
     */
    private function ttbp_get_extension_from_mime($mime_type) {
        $extensions = array(
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp'
        );
        return isset($extensions[strtolower($mime_type)]) ? $extensions[strtolower($mime_type)] : 'jpg';
    }
    
    /**
     * Get cover image URL for preview
     */
    public function ttbp_ajax_get_cover_image() {
        check_ajax_referer('ttbp_import_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'the-total-book-project')));
        }
        
        if (!isset($_POST['file_id'])) {
            wp_send_json_error(array('message' => __('File ID is required', 'the-total-book-project')));
        }
        
        $file_id = sanitize_text_field(wp_unslash($_POST['file_id']));
        $metadata = get_transient('ttbp_import_metadata_' . $file_id);
        
        if (!$metadata || empty($metadata['cover_path']) || !file_exists($metadata['cover_path'])) {
            wp_send_json_error(array('message' => __('Cover image not found', 'the-total-book-project')));
        }
        
        // Get file mime type
        $mime_type = wp_check_filetype($metadata['cover_path']);
        $mime_type = $mime_type['type'];
        
        // Read file and convert to base64
        $image_data = file_get_contents($metadata['cover_path']);
        $base64 = base64_encode($image_data);
        $data_url = 'data:' . $mime_type . ';base64,' . $base64;
        
        wp_send_json_success(array('url' => $data_url));
    }
    
    /**
     * Update import metadata (for chapter deletions)
     */
    public function ttbp_ajax_update_import_metadata() {
        check_ajax_referer('ttbp_import_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'the-total-book-project')));
        }
        
        if (!isset($_POST['file_id'])) {
            wp_send_json_error(array('message' => __('File ID is required', 'the-total-book-project')));
        }
        
        $file_id = sanitize_text_field(wp_unslash($_POST['file_id']));
        $metadata = get_transient('ttbp_import_metadata_' . $file_id);
        
        if (!$metadata) {
            wp_send_json_error(array('message' => __('Metadata not found', 'the-total-book-project')));
        }
        
        // Update chapters if provided
        if (isset($_POST['chapters'])) {
            $chapters_json = wp_unslash($_POST['chapters']);
            $chapters = json_decode($chapters_json, true);
            
            if (is_array($chapters)) {
                $metadata['chapters'] = $chapters;
                set_transient('ttbp_import_metadata_' . $file_id, $metadata, HOUR_IN_SECONDS);
            }
        }
        
        wp_send_json_success();
    }
    
    /**
     * Convert HTML content to WordPress blocks
     */
    private function ttbp_convert_html_to_blocks($html) {
        if (empty($html)) {
            return '';
        }
        
        // Check if DOMDocument is available
        if (!class_exists('DOMDocument')) {
            // Fallback: return sanitized HTML if DOMDocument is not available
            return wp_kses_post($html);
        }
        
        libxml_use_internal_errors(true);
        $dom = new DOMDocument('1.0', 'UTF-8');
        
        // Load HTML with proper encoding - wrap in body tag if needed
        $html_utf8 = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        
        // Check if HTML already has body tag
        if (stripos($html_utf8, '<body') === false) {
            $html_utf8 = '<body>' . $html_utf8 . '</body>';
        }
        
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html_utf8, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        $blocks = array();
        $body = $dom->getElementsByTagName('body')->item(0);
        
        if (!$body) {
            // Try to get document element if no body
            $doc_element = $dom->documentElement;
            if ($doc_element) {
                $body = $doc_element;
            } else {
                return wp_kses_post($html);
            }
        }
        
        // Process each child node
        foreach ($body->childNodes as $node) {
            $block = $this->ttbp_node_to_block($node, $dom);
            if ($block) {
                $blocks[] = $block;
            }
        }
        
        // Convert blocks array to block format string
        return $this->ttbp_serialize_blocks($blocks);
    }
    
    /**
     * Convert a DOM node to a WordPress block
     */
    private function ttbp_node_to_block($node, $dom) {
        if ($node->nodeType === XML_TEXT_NODE) {
            $text = trim($node->textContent);
            if (empty($text)) {
                return null;
            }
            // Wrap text nodes in paragraph blocks - sanitize the text
            $sanitized_text = wp_kses_post($text);
            return array(
                'blockName' => 'core/paragraph',
                'attrs' => array(),
                'innerContent' => array($sanitized_text),
                'innerHTML' => '<p>' . $sanitized_text . '</p>'
            );
        }
        
        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return null;
        }
        
        $tag_name = strtolower($node->tagName);
        
        switch ($tag_name) {
            case 'p':
                $content = $this->ttbp_get_inner_html($node, $dom);
                $sanitized_content = wp_kses_post($content);
                return array(
                    'blockName' => 'core/paragraph',
                    'attrs' => array(),
                    'innerContent' => array($sanitized_content),
                    'innerHTML' => '<p>' . $sanitized_content . '</p>'
                );
                
            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'h6':
                $level = (int) substr($tag_name, 1);
                $content = $this->ttbp_get_inner_html($node, $dom);
                $sanitized_content = wp_kses_post($content);
                return array(
                    'blockName' => 'core/heading',
                    'attrs' => array('level' => $level),
                    'innerContent' => array($sanitized_content),
                    'innerHTML' => '<h' . $level . '>' . $sanitized_content . '</h' . $level . '>'
                );
                
            case 'ul':
                $list_items = $this->ttbp_extract_list_items($node, $dom, false);
                $inner_content = array();
                $inner_html_parts = array();
                
                foreach ($list_items as $item) {
                    $sanitized_item = wp_kses_post($item['content']);
                    $inner_content[] = $sanitized_item;
                    $inner_html_parts[] = '<li>' . $sanitized_item . '</li>';
                }
                
                return array(
                    'blockName' => 'core/list',
                    'attrs' => array('ordered' => false),
                    'innerContent' => $inner_content,
                    'innerHTML' => '<ul>' . implode('', $inner_html_parts) . '</ul>'
                );
                
            case 'ol':
                $list_items = $this->ttbp_extract_list_items($node, $dom, true);
                $inner_content = array();
                $inner_html_parts = array();
                
                foreach ($list_items as $item) {
                    $sanitized_item = wp_kses_post($item['content']);
                    $inner_content[] = $sanitized_item;
                    $inner_html_parts[] = '<li>' . $sanitized_item . '</li>';
                }
                
                return array(
                    'blockName' => 'core/list',
                    'attrs' => array('ordered' => true),
                    'innerContent' => $inner_content,
                    'innerHTML' => '<ol>' . implode('', $inner_html_parts) . '</ol>'
                );
                
            case 'blockquote':
                $content = $this->ttbp_get_inner_html($node, $dom);
                $sanitized_content = wp_kses_post($content);
                return array(
                    'blockName' => 'core/quote',
                    'attrs' => array(),
                    'innerContent' => array($sanitized_content),
                    'innerHTML' => '<blockquote>' . $sanitized_content . '</blockquote>'
                );
                
            case 'pre':
            case 'code':
                $content = $this->ttbp_get_inner_html($node, $dom);
                // For code blocks, preserve the content but escape HTML
                $escaped_content = esc_html($content);
                return array(
                    'blockName' => 'core/code',
                    'attrs' => array(),
                    'innerContent' => array($escaped_content),
                    'innerHTML' => '<pre><code>' . $escaped_content . '</code></pre>'
                );
                
            case 'hr':
                return array(
                    'blockName' => 'core/separator',
                    'attrs' => array(),
                    'innerContent' => array(),
                    'innerHTML' => '<hr class="wp-block-separator"/>'
                );
                
            case 'div':
            case 'section':
            case 'article':
                // For div/section/article, try to extract meaningful content
                $content = $this->ttbp_get_inner_html($node, $dom);
                if (!empty(trim(strip_tags($content)))) {
                    $sanitized_content = wp_kses_post($content);
                    // Wrap in a paragraph block
                    return array(
                        'blockName' => 'core/paragraph',
                        'attrs' => array(),
                        'innerContent' => array($sanitized_content),
                        'innerHTML' => '<p>' . $sanitized_content . '</p>'
                    );
                }
                return null;
                
            default:
                // For unknown elements, try to preserve as paragraph
                $content = $this->ttbp_get_inner_html($node, $dom);
                if (!empty(trim(strip_tags($content)))) {
                    $sanitized_content = wp_kses_post($content);
                    return array(
                        'blockName' => 'core/paragraph',
                        'attrs' => array(),
                        'innerContent' => array($sanitized_content),
                        'innerHTML' => '<p>' . $sanitized_content . '</p>'
                    );
                }
                return null;
        }
    }
    
    /**
     * Get inner HTML of a node
     */
    private function ttbp_get_inner_html($node, $dom) {
        $inner_html = '';
        foreach ($node->childNodes as $child) {
            $inner_html .= $dom->saveHTML($child);
        }
        return trim($inner_html);
    }
    
    /**
     * Extract list items from ul/ol
     */
    private function ttbp_extract_list_items($list_node, $dom, $ordered) {
        $items = array();
        foreach ($list_node->getElementsByTagName('li') as $li) {
            $content = $this->ttbp_get_inner_html($li, $dom);
            $items[] = array('content' => $content);
        }
        return $items;
    }
    
    /**
     * Serialize blocks array to WordPress block format
     */
    private function ttbp_serialize_blocks($blocks) {
        if (empty($blocks)) {
            return '';
        }
        
        $output = '';
        foreach ($blocks as $block) {
            if (empty($block['blockName'])) {
                continue;
            }
            
            $block_name = $block['blockName'];
            $attrs = !empty($block['attrs']) ? json_encode($block['attrs']) : '{}';
            $inner_html = isset($block['innerHTML']) ? $block['innerHTML'] : '';
            
            // WordPress block format: <!-- wp:block-name {"attrs":{}} -->
            $output .= '<!-- wp:' . $block_name . ' ' . $attrs . ' -->' . "\n";
            $output .= $inner_html . "\n";
            $output .= '<!-- /wp:' . $block_name . ' -->' . "\n";
        }
        
        return trim($output);
    }
    
    /**
     * Import cover image and set as featured image
     */
    private function ttbp_import_cover_image($book_id, $cover_path) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $filename = basename($cover_path);
        $file_array = array(
            'name' => $filename,
            'tmp_name' => $cover_path
        );
        
        $attachment_id = media_handle_sideload($file_array, $book_id);
        
        if (!is_wp_error($attachment_id)) {
            set_post_thumbnail($book_id, $attachment_id);
        }
    }
}

new TTBP_Import();

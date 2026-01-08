<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * TTBP_Block_Converter
 * 
 * Converts HTML content to WordPress blocks format.
 * Can be used standalone or as part of import processes.
 */
class TTBP_Block_Converter {
    
    /**
     * Convert HTML content to WordPress blocks format
     * 
     * @param string $html HTML content to convert
     * @param array $image_url_map Optional map of image URLs to attachment data
     * @return string WordPress blocks format string
     */
    public static function convert_html_to_blocks($html, $image_url_map = array()) {
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
        
        // Ensure HTML is UTF-8 encoded
        $html_utf8 = mb_convert_encoding($html, 'UTF-8', mb_detect_encoding($html, array('UTF-8', 'ISO-8859-1', 'Windows-1252'), true));
        
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
            $block = self::node_to_block($node, $dom, $image_url_map);
            if ($block) {
                // Handle multiple blocks (e.g., paragraph with image extracted, or div with children)
                if (isset($block[0]) && is_array($block[0])) {
                    $blocks = array_merge($blocks, $block);
                } else {
                    $blocks[] = $block;
                }
            }
        }
        
        // Convert blocks array to block format string
        return self::serialize_blocks($blocks);
    }
    
    /**
     * Convert a post's content to WordPress blocks format
     * 
     * @param int $post_id Post ID to convert
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public static function convert_post_to_blocks($post_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            return new WP_Error('post_not_found', __('Post not found.', 'the-total-book-project'));
        }
        
        // Get current content
        $html_content = $post->post_content;
        
        // If content is already in blocks format, skip
        if (strpos($html_content, '<!-- wp:') !== false) {
            return new WP_Error('already_blocks', __('Content is already in blocks format.', 'the-total-book-project'));
        }
        
        // Build image URL map from existing images in content
        $image_url_map = self::extract_image_url_map_from_content($html_content);
        
        // Convert to blocks
        $blocks_content = self::convert_html_to_blocks($html_content, $image_url_map);
        
        // Update post content
        $updated = wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $blocks_content
        ));
        
        if (is_wp_error($updated)) {
            return $updated;
        }
        
        return true;
    }
    
    /**
     * Extract image URL map from existing HTML content
     * 
     * @param string $html HTML content
     * @return array Image URL map
     */
    private static function extract_image_url_map_from_content($html) {
        $image_url_map = array();
        
        if (empty($html)) {
            return $image_url_map;
        }
        
        // Use regex to find all img tags
        preg_match_all('/<img[^>]+src=["\']([^"\'>\s]+)["\'][^>]*>/i', $html, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $img_url) {
                // Try to find attachment by URL
                $attachment_id = attachment_url_to_postid($img_url);
                if ($attachment_id) {
                    $image_url_map[$img_url] = array(
                        'url' => $img_url,
                        'id' => $attachment_id,
                        'alt' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
                        'title' => get_the_title($attachment_id)
                    );
                }
            }
        }
        
        return $image_url_map;
    }
    
    /**
     * Convert a DOM node to a WordPress block
     */
    private static function node_to_block($node, $dom, $image_url_map = array()) {
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
                // Check if paragraph contains only an image
                $img_nodes = $node->getElementsByTagName('img');
                if ($img_nodes->length === 1 && trim($node->textContent) === '') {
                    // Paragraph contains only an image, convert to image block
                    return self::node_to_block($img_nodes->item(0), $dom, $image_url_map);
                }
                
                // Check if paragraph contains images mixed with text
                $xpath = new DOMXPath($dom);
                $paragraph_imgs = $xpath->query('.//img', $node);
                if ($paragraph_imgs->length > 0) {
                    // Extract images and text separately
                    $blocks = array();
                    $current_text = '';
                    
                    foreach ($node->childNodes as $child) {
                        if ($child->nodeType === XML_ELEMENT_NODE && strtolower($child->tagName) === 'img') {
                            // Save any accumulated text as paragraph
                            if (!empty(trim($current_text))) {
                                $blocks[] = array(
                                    'blockName' => 'core/paragraph',
                                    'attrs' => array(),
                                    'innerContent' => array(wp_kses_post($current_text)),
                                    'innerHTML' => '<p>' . wp_kses_post($current_text) . '</p>'
                                );
                                $current_text = '';
                            }
                            // Add image block
                            $img_block = self::node_to_block($child, $dom, $image_url_map);
                            if ($img_block) {
                                $blocks[] = $img_block;
                            }
                        } else {
                            $current_text .= $dom->saveHTML($child);
                        }
                    }
                    
                    // Add remaining text as paragraph
                    if (!empty(trim($current_text))) {
                        $blocks[] = array(
                            'blockName' => 'core/paragraph',
                            'attrs' => array(),
                            'innerContent' => array(wp_kses_post($current_text)),
                            'innerHTML' => '<p>' . wp_kses_post($current_text) . '</p>'
                        );
                    }
                    
                    return !empty($blocks) ? $blocks : null;
                }
                
                // Regular paragraph with no images
                $content = self::get_inner_html($node, $dom);
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
                $content = self::get_inner_html($node, $dom);
                $sanitized_content = wp_kses_post($content);
                return array(
                    'blockName' => 'core/heading',
                    'attrs' => array('level' => $level),
                    'innerContent' => array($sanitized_content),
                    'innerHTML' => '<h' . $level . '>' . $sanitized_content . '</h' . $level . '>'
                );
                
            case 'ul':
                $list_items = self::extract_list_items($node, $dom, false);
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
                $list_items = self::extract_list_items($node, $dom, true);
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
                $content = self::get_inner_html($node, $dom);
                $sanitized_content = wp_kses_post($content);
                return array(
                    'blockName' => 'core/quote',
                    'attrs' => array(),
                    'innerContent' => array($sanitized_content),
                    'innerHTML' => '<blockquote>' . $sanitized_content . '</blockquote>'
                );
                
            case 'pre':
            case 'code':
                $content = self::get_inner_html($node, $dom);
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
                
            case 'img':
                if (!($node instanceof DOMElement)) {
                    return null;
                }
                
                $img_src = $node->getAttribute('src');
                $img_alt = $node->getAttribute('alt') ?: '';
                $img_title = $node->getAttribute('title') ?: '';
                
                // Find image in URL map (map uses URLs as keys)
                $image_data = null;
                if (isset($image_url_map[$img_src])) {
                    $image_data = $image_url_map[$img_src];
                } else {
                    // Try partial match (in case of query strings or different protocols)
                    foreach ($image_url_map as $url => $image_info) {
                        if ($url === $img_src || strpos($img_src, $url) !== false || strpos($url, $img_src) !== false) {
                            $image_data = $image_info;
                            break;
                        }
                    }
                }
                
                // If not found in map, try to find by URL directly
                if (!$image_data) {
                    // Image URL might already be replaced, try to find attachment by URL
                    $attachment_id = attachment_url_to_postid($img_src);
                    if ($attachment_id) {
                        $image_data = array(
                            'url' => $img_src,
                            'id' => $attachment_id,
                            'alt' => $img_alt,
                            'title' => $img_title
                        );
                    }
                }
                
                if ($image_data && isset($image_data['id'])) {
                    $attrs = array(
                        'id' => intval($image_data['id']),
                        'sizeSlug' => 'full'
                    );
                    
                    if (!empty($img_alt)) {
                        $attrs['alt'] = $img_alt;
                    }
                    
                    $img_html = '<figure class="wp-block-image">';
                    $img_html .= '<img src="' . esc_url($image_data['url']) . '"';
                    if (!empty($img_alt)) {
                        $img_html .= ' alt="' . esc_attr($img_alt) . '"';
                    }
                    if (!empty($img_title)) {
                        $img_html .= ' title="' . esc_attr($img_title) . '"';
                    }
                    $img_html .= '/></figure>';
                    
                    return array(
                        'blockName' => 'core/image',
                        'attrs' => $attrs,
                        'innerContent' => array($img_html),
                        'innerHTML' => $img_html
                    );
                }
                
                // Fallback: image not found, return as-is
                return array(
                    'blockName' => 'core/image',
                    'attrs' => array(),
                    'innerContent' => array($dom->saveHTML($node)),
                    'innerHTML' => $dom->saveHTML($node)
                );
                
            case 'div':
            case 'section':
            case 'article':
                // For div/section/article, recursively process children to convert to blocks
                $child_blocks = array();
                foreach ($node->childNodes as $child) {
                    $child_block = self::node_to_block($child, $dom, $image_url_map);
                    if ($child_block) {
                        // Handle multiple blocks (e.g., paragraph with image extracted)
                        if (isset($child_block[0]) && is_array($child_block[0])) {
                            $child_blocks = array_merge($child_blocks, $child_block);
                        } else {
                            $child_blocks[] = $child_block;
                        }
                    }
                }
                
                // If we have blocks, return them (they'll be flattened by the parent)
                if (!empty($child_blocks)) {
                    return $child_blocks;
                }
                
                // If no meaningful content, return null to skip
                return null;
                
            default:
                // For unknown block-level elements, recursively process children
                $child_blocks = array();
                foreach ($node->childNodes as $child) {
                    $child_block = self::node_to_block($child, $dom, $image_url_map);
                    if ($child_block) {
                        // Skip inline elements (they have blockName === null)
                        if (isset($child_block['blockName']) && $child_block['blockName'] === null) {
                            continue;
                        }
                        // Handle multiple blocks
                        if (isset($child_block[0]) && is_array($child_block[0])) {
                            $child_blocks = array_merge($child_blocks, $child_block);
                        } else {
                            $child_blocks[] = $child_block;
                        }
                    }
                }
                
                // If we have blocks, return them
                if (!empty($child_blocks)) {
                    return $child_blocks;
                }
                
                // If no meaningful content, try to extract text and wrap in paragraph
                $content = self::get_inner_html($node, $dom);
                $text_content = trim(strip_tags($content));
                if (!empty($text_content)) {
                    $sanitized_content = wp_kses_post($content);
                    // Remove any remaining div tags and other block-level elements
                    $sanitized_content = preg_replace('/<\/?div[^>]*>/i', '', $sanitized_content);
                    $sanitized_content = preg_replace('/<\/?section[^>]*>/i', '', $sanitized_content);
                    $sanitized_content = preg_replace('/<\/?article[^>]*>/i', '', $sanitized_content);
                    if (!empty(trim($sanitized_content))) {
                        return array(
                            'blockName' => 'core/paragraph',
                            'attrs' => array(),
                            'innerContent' => array($sanitized_content),
                            'innerHTML' => '<p>' . $sanitized_content . '</p>'
                        );
                    }
                }
                return null;
        }
    }
    
    /**
     * Get inner HTML of a node
     */
    private static function get_inner_html($node, $dom) {
        $inner_html = '';
        foreach ($node->childNodes as $child) {
            $inner_html .= $dom->saveHTML($child);
        }
        return trim($inner_html);
    }
    
    /**
     * Extract list items from ul/ol
     */
    private static function extract_list_items($list_node, $dom, $ordered) {
        $items = array();
        foreach ($list_node->getElementsByTagName('li') as $li) {
            $content = self::get_inner_html($li, $dom);
            $items[] = array('content' => $content);
        }
        return $items;
    }
    
    /**
     * Serialize blocks array to WordPress block format
     */
    private static function serialize_blocks($blocks) {
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
}


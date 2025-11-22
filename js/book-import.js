(function($) {
    'use strict';
    
    let currentFileId = null;
    let currentMetadata = null;
    
    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    /**
     * Display chapters in preview
     */
    function displayChapters(metadata) {
        const $chaptersSection = $('#ttbp-preview-chapters');
        const $chaptersList = $('#ttbp-preview-chapters-list');
        
        if (!metadata) {
            metadata = currentMetadata;
        }
        
        if (metadata && metadata.chapters && metadata.chapters.length > 0) {
            let chaptersHtml = '<ul class="ttbp-chapters-preview-list">';
            metadata.chapters.forEach(function(chapter, index) {
                // Extract preview text from chapter content
                let preview = '';
                if (chapter.content) {
                    // Strip HTML tags and get first 150 characters
                    const textContent = chapter.content.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
                    preview = textContent.substring(0, 150);
                    if (textContent.length > 150) {
                        preview += '...';
                    }
                }
                
                chaptersHtml += '<li data-chapter-index="' + index + '">' + 
                    '<div class="ttbp-chapter-header">' +
                    '<span class="ttbp-chapter-number">' + (index + 1) + '.</span> ' +
                    '<span class="ttbp-chapter-title">' + (chapter.title || 'Chapter ' + (index + 1)) + '</span>' +
                    '<button type="button" class="ttbp-delete-chapter-btn" data-index="' + index + '" title="Delete chapter">' +
                    '<span class="dashicons dashicons-trash"></span>' +
                    '</button>' +
                    '</div>';
                
                if (preview) {
                    chaptersHtml += '<div class="ttbp-chapter-preview">' + escapeHtml(preview) + '</div>';
                }
                
                chaptersHtml += '</li>';
            });
            chaptersHtml += '</ul>';
            $chaptersList.html(chaptersHtml);
            $chaptersSection.show();
            
            // Attach delete handlers
            $('.ttbp-delete-chapter-btn').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const index = parseInt($(this).data('index'));
                deleteChapter(index);
            });
        } else {
            $chaptersSection.hide();
        }
    }
    
    /**
     * Delete a chapter from the preview
     */
    function deleteChapter(index) {
        if (!currentMetadata || !currentMetadata.chapters || !currentMetadata.chapters[index]) {
            return;
        }
        
        // Remove chapter from array
        currentMetadata.chapters.splice(index, 1);
        
        // Re-render chapters list
        displayChapters(currentMetadata);
        
        // Update the stored metadata in transient (via AJAX)
        // This ensures the updated chapters are used when creating the book
        $.ajax({
            url: ttbpImport.ajaxurl,
            type: 'POST',
            data: {
                action: 'ttbp_update_import_metadata',
                nonce: ttbpImport.nonce,
                file_id: currentFileId,
                chapters: JSON.stringify(currentMetadata.chapters)
            },
            success: function(response) {
                // Metadata updated successfully
            },
            error: function() {
                // Silently fail - chapters are already updated in memory
            }
        });
    }
    
    $(document).ready(function() {
        const $uploadArea = $('#ttbp-upload-area');
        const $fileInput = $('#ttbp-file-input');
        const $browseBtn = $('#ttbp-browse-btn');
        const $metadataPreview = $('#ttbp-metadata-preview');
        const $createBookBtn = $('#ttbp-create-book');
        const $cancelBtn = $('#ttbp-cancel-import');
        
        // Browse button click
        $browseBtn.on('click', function() {
            $fileInput.click();
        });
        
        // File input change
        $fileInput.on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                handleFileUpload(file);
            }
        });
        
        // Drag and drop handlers
        $uploadArea.on('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('dragover');
        });
        
        $uploadArea.on('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('dragover');
        });
        
        $uploadArea.on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('dragover');
            
            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                handleFileUpload(files[0]);
            }
        });
        
        // Create book button
        $createBookBtn.on('click', function() {
            if (!currentFileId) {
                alert(ttbpImport.messages.error);
                return;
            }
            
            createBookFromImport();
        });
        
        // Cancel button
        $cancelBtn.on('click', function() {
            resetImport();
        });
        
        /**
         * Handle file upload
         */
        function handleFileUpload(file) {
            // Validate file type
            if (!file.name.toLowerCase().endsWith('.epub')) {
                alert(ttbpImport.messages.invalidFile);
                return;
            }
            
            // Show progress
            showProgress(ttbpImport.messages.uploading);
            
            // Create FormData
            const formData = new FormData();
            formData.append('action', 'ttbp_upload_epub');
            formData.append('nonce', ttbpImport.nonce);
            formData.append('file', file);
            
            // Upload file
            $.ajax({
                url: ttbpImport.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        currentFileId = response.data.file_id;
                        extractMetadata(currentFileId);
                    } else {
                        hideProgress();
                        alert(response.data.message || ttbpImport.messages.error);
                    }
                },
                error: function() {
                    hideProgress();
                    alert(ttbpImport.messages.error);
                }
            });
        }
        
        /**
         * Extract metadata from uploaded file
         */
        function extractMetadata(fileId) {
            showProgress(ttbpImport.messages.extracting);
            
            $.ajax({
                url: ttbpImport.ajaxurl,
                type: 'POST',
                data: {
                    action: 'ttbp_extract_metadata',
                    nonce: ttbpImport.nonce,
                    file_id: fileId
                },
                success: function(response) {
                    hideProgress();
                    
                    if (response.success) {
                        currentMetadata = response.data;
                        displayMetadata(currentMetadata);
                    } else {
                        alert(response.data.message || ttbpImport.messages.error);
                    }
                },
                error: function() {
                    hideProgress();
                    alert(ttbpImport.messages.error);
                }
            });
        }
        
        /**
         * Display extracted metadata
         */
        function displayMetadata(metadata) {
            // Set values
            $('#ttbp-preview-title').text(metadata.title || '—');
            $('#ttbp-preview-subtitle').text(metadata.subtitle || '—');
            $('#ttbp-preview-authors').text(metadata.authors && metadata.authors.length > 0 ? metadata.authors.join(', ') : '—');
            $('#ttbp-preview-publisher').text(metadata.publisher || '—');
            $('#ttbp-preview-date').text(metadata.publication_date || '—');
            $('#ttbp-preview-isbn').text(metadata.isbn || '—');
            $('#ttbp-preview-description').text(metadata.description || '—');
            
            // Display chapters
            displayChapters(metadata);
            
            // Handle cover image
            const $coverImg = $('#ttbp-preview-cover-img');
            const $noCover = $('.ttbp-no-cover');
            
            if (metadata.cover_image) {
                // Use the base64 data URL directly from metadata
                $coverImg.attr('src', metadata.cover_image).show();
                $noCover.hide();
            } else {
                $coverImg.hide();
                $noCover.show();
            }
            
            // Show preview, hide upload area
            $uploadArea.hide();
            $metadataPreview.show();
        }
        
        /**
         * Create book from import
         */
        function createBookFromImport() {
            if (!currentFileId) {
                alert(ttbpImport.messages.error);
                return;
            }
            
            showProgress(ttbpImport.messages.creating);
            $createBookBtn.prop('disabled', true);
            
            $.ajax({
                url: ttbpImport.ajaxurl,
                type: 'POST',
                data: {
                    action: 'ttbp_create_book_from_import',
                    nonce: ttbpImport.nonce,
                    file_id: currentFileId
                },
                success: function(response) {
                    hideProgress();
                    $createBookBtn.prop('disabled', false);
                    
                    if (response.success) {
                        // Redirect to edit book page
                        if (response.data.edit_url) {
                            window.location.href = response.data.edit_url;
                        } else {
                            alert('Book created successfully!');
                            resetImport();
                        }
                    } else {
                        alert(response.data.message || ttbpImport.messages.error);
                    }
                },
                error: function() {
                    hideProgress();
                    $createBookBtn.prop('disabled', false);
                    alert(ttbpImport.messages.error);
                }
            });
        }
        
        /**
         * Show progress indicator
         */
        function showProgress(message) {
            const $progress = $('.ttbp-upload-progress');
            const $progressText = $('.ttbp-progress-text');
            const $progressFill = $('.ttbp-progress-fill');
            
            $progressText.text(message);
            $progress.show();
            $progressFill.css('width', '0%');
            
            // Animate progress bar
            setTimeout(function() {
                $progressFill.css('width', '50%');
            }, 100);
        }
        
        /**
         * Hide progress indicator
         */
        function hideProgress() {
            const $progress = $('.ttbp-upload-progress');
            const $progressFill = $('.ttbp-progress-fill');
            
            $progressFill.css('width', '100%');
            
            setTimeout(function() {
                $progress.hide();
                $progressFill.css('width', '0%');
            }, 300);
        }
        
        /**
         * Reset import form
         */
        function resetImport() {
            currentFileId = null;
            currentMetadata = null;
            $fileInput.val('');
            $uploadArea.show();
            $metadataPreview.hide();
            hideProgress();
        }
    });
    
})(jQuery);


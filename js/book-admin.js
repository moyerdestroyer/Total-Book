jQuery(document).ready(function($) {
    // Initialize sortable chapters list
    $('.chapters-sortable').sortable({
        handle: '.dashicons-menu',
        update: function(event, ui) {
            var order = [];
            $('.chapter-item').each(function() {
                order.push($(this).data('id'));
            });
            
            $.ajax({
                url: ttbpAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'ttbp_update_chapter_order',
                    nonce: ttbpAdmin.nonce,
                    order: JSON.stringify(order)
                },
                success: function(response) {
                    if (!response.success) {
                        alert('Failed to update chapter order');
                    }
                }
            });
        }
    });

    // Add new chapter
    $('#add_chapter').on('click', function() {
        var title = $('#new_chapter_title').val();
        if (!title) {
            alert('Please enter a chapter title');
            return;
        }

        $.ajax({
            url: ttbpAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'ttbp_add_chapter',
                nonce: ttbpAdmin.nonce,
                book_id: $('#post_ID').val(),
                title: title
            },
            success: function(response) {
                if (response.success) {
                    var chapter = response.data;
                    var html = '<li class="chapter-item" data-id="' + chapter.id + '">' +
                        '<span class="dashicons dashicons-menu"></span>' +
                        '<a href="' + chapter.edit_link + '">' + chapter.title + '</a>' +
                        '<span class="chapter-actions">' +
                        '<a href="' + chapter.edit_link + '" class="button button-small">Edit</a>' +
                        '<a href="#" class="button button-small delete-chapter" data-id="' + chapter.id + '">Delete</a>' +
                        '</span></li>';
                    
                    if ($('.no-chapters').length) {
                        $('.no-chapters').remove();
                        $('.chapters-list').append('<ul class="chapters-sortable"></ul>');
                    }
                    $('.chapters-sortable').append(html);
                    $('#new_chapter_title').val('');
                } else {
                    alert('Failed to add chapter');
                }
            }
        });
    });

    // Delete chapter
    $(document).on('click', '.delete-chapter', function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete this chapter?')) {
            return;
        }

        var chapterId = $(this).data('id');
        var $chapter = $(this).closest('.chapter-item');

        $.ajax({
            url: ttbpAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'ttbp_delete_chapter',
                nonce: ttbpAdmin.nonce,
                chapter_id: chapterId
            },
            success: function(response) {
                if (response.success) {
                    $chapter.remove();
                    if ($('.chapter-item').length === 0) {
                        $('.chapters-sortable').remove();
                        $('.chapters-list').append('<p class="no-chapters">No chapters added yet.</p>');
                    }
                } else {
                    alert('Failed to delete chapter');
                }
            }
        });
    });

    // Chapter assignment functionality
    // Show/hide assignment dropdown
    $(document).on('click', '.assign-chapter-btn', function(e) {
        e.preventDefault();
        var $container = $(this).closest('.assign-chapter-container');
        $('.assign-chapter-dropdown').not($container.find('.assign-chapter-dropdown')).hide();
        $container.find('.assign-chapter-dropdown').toggle();
    });

    // Hide dropdown when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.assign-chapter-container').length) {
            $('.assign-chapter-dropdown').hide();
        }
    });

    // Cancel button
    $(document).on('click', '.cancel-btn', function(e) {
        e.preventDefault();
        $(this).closest('.assign-chapter-dropdown').hide();
    });

    // Assign chapter to book
    $(document).on('click', '.assign-btn', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var chapterId = $btn.data('chapter-id');
        var bookId = $btn.closest('.assign-chapter-dropdown').find('.book-select').val();

        if (!bookId) {
            alert(ttbpAdmin.messages.selectBook || 'Please select a book');
            return;
        }

        $btn.prop('disabled', true).text(ttbpAdmin.messages.assigning || 'Assigning...');

        $.ajax({
            url: ttbpAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'ttbp_assign_chapter_to_book',
                nonce: ttbpAdmin.nonce,
                chapter_id: chapterId,
                book_id: bookId
            },
            success: function(response) {
                if (response.success) {
                    // Update the parent book column
                    var $container = $btn.closest('.assign-chapter-container');
                    $container.html('<a href="' + response.data.edit_link + '">' + response.data.book_title + '</a>');
                    
                    // Update the position column
                    var $row = $container.closest('tr');
                    $row.find('.column-chapter_position').html('<span style="font-weight: bold; color: #2271b1;">-</span>');
                    
                    // Show success message
                    alert(response.data.message);
                } else {
                    alert(ttbpAdmin.messages.assignFailed || 'Failed to assign chapter');
                }
            },
            error: function() {
                alert(ttbpAdmin.messages.assignFailed || 'Failed to assign chapter');
            },
            complete: function() {
                $btn.prop('disabled', false).text(ttbpAdmin.messages.assign || 'Assign');
            }
        });
    });

    // Tagify functionality for book authors
    var input = document.getElementById('book_authors_tagify');
    if (input) {
        var tagify = new Tagify(input, {
            whitelist: [], // Will be populated via AJAX
            maxTags: 10,
            trim: true,
            duplicates: false,
            placeholder: ttbpAdmin.messages.authorPlaceholder || 'Type author name and press Enter',
            dropdown: {
                enabled: 1, // Show suggestions dropdown
                maxItems: 10, // Maximum number of suggestions to show
                classname: 'tagify-dropdown', // Custom class for styling
                enabled: 1,
                closeOnSelect: false // Keep dropdown open for multiple selections
            },
            enforceWhitelist: false, // Allow custom tags not in whitelist
            editTags: 1, // Allow editing tags by clicking on them
            transformTag: function(tagData) {
                // Add count information if available
                if (tagData.count) {
                    tagData.title = tagData.value + ' (' + tagData.count + ' books)';
                }
                return tagData;
            }
        });

        // Load initial authors for suggestions
        $.ajax({
            url: ttbpAdmin.ajaxurl,
            type: 'GET',
            data: {
                action: 'ttbp_get_authors',
                nonce: ttbpAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    tagify.settings.whitelist = response.data;
                }
            }
        });

        // Handle search input for dynamic suggestions
        tagify.on('input', function(e) {
            var value = e.detail.value;
            if (value.length < 2) return; // Don't search for very short strings

            $.ajax({
                url: ttbpAdmin.ajaxurl,
                type: 'GET',
                data: {
                    action: 'ttbp_get_authors',
                    nonce: ttbpAdmin.nonce,
                    search: value
                },
                success: function(response) {
                    if (response.success) {
                        tagify.settings.whitelist = response.data;
                        tagify.dropdown.show.call(tagify, value); // Show dropdown with filtered results
                    }
                }
            });
        });

        $('#post').on('submit', function(e) {
            var tagData = tagify.value;
            if (!tagData || tagData.length === 0) {
                e.preventDefault();
                alert(ttbpAdmin.messages.authorRequired || 'At least one author is required. Please add an author.');
                input.focus();
                return false;
            }
            // Before submit, set a hidden field with the author names (comma separated)
            if ($('#book_authors_hidden').length === 0) {
                $('<input>').attr({type: 'hidden', id: 'book_authors_hidden', name: 'book_authors_tagify_hidden'}).appendTo($(this));
            }
            $('#book_authors_hidden').val(tagData.map(function(tag){return tag.value;}).join(','));
            // Remove name attribute from the visible input so only the hidden field is submitted
            $('#book_authors_tagify').removeAttr('name');
        });
    }
});
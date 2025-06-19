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
                url: totalBookAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'update_chapter_order',
                    nonce: totalBookAdmin.nonce,
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
            url: totalBookAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'add_chapter',
                nonce: totalBookAdmin.nonce,
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
            url: totalBookAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_chapter',
                nonce: totalBookAdmin.nonce,
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
}); 
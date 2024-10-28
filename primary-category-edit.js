jQuery(document).ready(function($) {
    $('.pce-primary-category-selector').on('change', function() {
        const postId = $(this).data('post-id');
        const categoryId = $(this).val();
        
        $.ajax({
            url: pcePrimaryCategoryAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'pce_update_primary_category',
                post_id: postId,
                category_id: categoryId,
                nonce: pcePrimaryCategoryAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Primary category updated successfully!');
                } else {
                    alert('Error updating primary category.');
                }
            }
        });
    });
});

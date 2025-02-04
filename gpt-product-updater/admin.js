jQuery(document).ready(function($) {
    $('#update-gpt-descriptions').click(function() {
        $.ajax({
            url: gptPdu.ajax_url,
            type: 'POST',
            data: {
                action: 'gpt_pdu_update_descriptions'
            },
            success: function(response) {
                if (response.success) {
                    alert('Descriptions updated successfully');
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                var errorMessage = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message
                    ? xhr.responseJSON.data.message
                    : 'AJAX error: ' + error;
                alert(errorMessage);
            }
        });
    });

    $(document).on('click', '#gpt-update-current-product', function(e) {
        e.preventDefault();  // Prevent the default form submission
        var productId = $(this).data('product-id');
        $.ajax({
            url: gptPdu.ajax_url,
            type: 'POST',
            data: {
                action: 'gpt_pdu_update_current_product',
                product_id: productId
            },
            success: function(response) {
                if (response.success) {
                    alert('Product description updated successfully');
                    // Optionally refresh the page or update the UI
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function(xhr, status, error) {
                var errorMessage = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message
                    ? xhr.responseJSON.data.message
                    : 'AJAX error: ' + error;
                alert(errorMessage);
            }
        });
    });
});

/**
 * France Relocation Assistant - Admin JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Manual update button
        $('#fra-manual-update').on('click', function() {
            const $btn = $(this);
            const $spinner = $('#fra-update-spinner');
            const $result = $('#fra-update-result');
            
            $btn.prop('disabled', true);
            $spinner.addClass('is-active');
            $result.hide();
            
            $.ajax({
                url: fraAdminData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'fra_manual_update',
                    nonce: fraAdminData.nonce
                },
                success: function(response) {
                    $spinner.removeClass('is-active');
                    $btn.prop('disabled', false);
                    
                    if (response.success) {
                        const data = response.data;
                        let html = '<strong>Update completed!</strong><br>';
                        html += 'Status: ' + data.status + '<br>';
                        html += 'Message: ' + data.message + '<br>';
                        
                        if (data.updates && data.updates.length > 0) {
                            html += '<br><strong>Updates:</strong><ul>';
                            data.updates.forEach(function(update) {
                                html += '<li>' + update + '</li>';
                            });
                            html += '</ul>';
                        }
                        
                        $result.html(html).show();
                        
                        // Reload page after 2 seconds to show updated status
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        $result.html('<strong>Error:</strong> ' + response.data).show();
                    }
                },
                error: function() {
                    $spinner.removeClass('is-active');
                    $btn.prop('disabled', false);
                    $result.html('<strong>Error:</strong> Failed to connect to server.').show();
                }
            });
        });
        
    });
    
})(jQuery);

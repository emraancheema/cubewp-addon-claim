jQuery(document).on('click','#cwp-claim-approve', function(e) {
    e.preventDefault();
    var thisObj = jQuery(this);
    var pid = thisObj.data('pid');
    var data = {
            'action': 'cwp_claim_approve',
            'post_id': pid,
        };
    jQuery.ajax({
            url: cwp_vars_params.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    thisObj.html('Approved');
                    alert(response.data);
                    location.reload();
                } else {
                    alert(response.data);
                }
            }
    });
});

jQuery(document).on('click','#cwp-claim-reject', function(e) {
    e.preventDefault();
    var thisObj = jQuery(this);
    var pid = thisObj.data('pid');
    var data = {
            'action': 'cwp_claim_reject',
            'post_id': pid,
        };
    jQuery.ajax({
            url: cwp_vars_params.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    thisObj.html('Rejected');
                    alert(response.data);
                    location.reload();
                } else {
                    alert(response.data);
                }
            }
    });
});
jQuery(document).ready(function () {
    jQuery(document).on("click", ".cwp-claim-form-container .cwp-claim-form-close", function () {
        jQuery(".cwp-claim-form-container .cwp-claim-form-modal").removeClass("cwp-claim-visible");
        jQuery(".cwp-claim-form-container .cwp-claim-form-modal").removeClass("cwp-claim-form-visible");
    });
    jQuery(document).on("click", ".cwp-claim-form-container #cwp-claim-form-btn", function () {
        jQuery(".cwp-claim-form-container .cwp-claim-form-modal").addClass("cwp-claim-visible");

    });
    jQuery(document).on("click", ".cwp-claim-form-modal.cwp-claim-visible .cwp-plan-submit", function () {
        event.preventDefault();
        var planId = jQuery(this).siblings('input[name="plan_id"]').val();
        var id = jQuery('.cwp-claim-form-container #cwp-claim-form-btn').data('pid');
        jQuery('#cwp-from-cwp_claim input[name="cwp_user_form[plan_id]"]').val(planId);
        var data = {
            'action': 'cwp_claim_plan_associated_form',
            id: id,
            plan_id: planId,
            nonce: cwp_claim_frontend_params.nonce
        };
        jQuery.ajax({
            url: cwp_claim_frontend_params.ajax_url,
            type: 'post',
            data: data,
            success: function (response) {
                jQuery('.cwp-claim-paid-form').html(response.output);
            }
        });
        jQuery(".cwp-claim-form-modal.cwp-claim-visible").addClass("cwp-claim-form-visible");

    });

});
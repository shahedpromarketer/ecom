/* Clothonic CRM Connector – Admin JS */

jQuery(document).ready(function ($) {

    // Show / hide token
    window.toggleTokenVisibility = function () {
        var input = document.getElementById('clothonic_token_input');
        var btn   = document.getElementById('toggle-token');
        if (input.type === 'password') {
            input.type   = 'text';
            btn.textContent = '🙈';
        } else {
            input.type   = 'password';
            btn.textContent = '👁';
        }
    };

    // Show/hide hours field when duplicate toggle changes
    $('#duplicate-toggle').on('change', function () {
        if ($(this).is(':checked')) {
            $('#hours-field').slideDown(200);
        } else {
            $('#hours-field').slideUp(200);
        }
    });

    // Test connection
    window.testConnection = function () {
        var token   = $('input[name="clothonic_access_token"]').val().trim();
        var crmUrl  = $('input[name="clothonic_crm_url"]').val().trim();
        var $status = $('#connection-status');
        var $btn    = $('#test-connection-btn');

        if (!token) {
            $status.attr('class', 'error').text('❌ Please enter an access token first');
            return;
        }
        if (!crmUrl) {
            $status.attr('class', 'error').text('❌ Please enter the CRM URL first');
            return;
        }

        $status.attr('class', 'loading').text('⏳ Testing connection…');
        $btn.prop('disabled', true);

        $.ajax({
            url:  clothonic_ajax.ajax_url,
            type: 'POST',
            data: {
                action:  'clothonic_test_connection',
                token:   token,
                crm_url: crmUrl,
                nonce:   clothonic_ajax.nonce,
            },
            success: function (res) {
                if (res.success) {
                    $status.attr('class', 'success').text('✅ ' + res.data.message);
                } else {
                    $status.attr('class', 'error').text('❌ ' + res.data.message);
                }
            },
            error: function () {
                $status.attr('class', 'error').text('❌ Request failed. Check your CRM URL.');
            },
            complete: function () {
                $btn.prop('disabled', false);
            },
        });
    };

});

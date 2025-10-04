jQuery(function ($) {
    $('#mc-test-connection').on('click', function () {
        const $result = $('#mc-test-result')
        $result.removeClass('success error').text('Probando...')

        $.post(seocontentlocker_mailchimp.ajax_url, {
            action: 'seocontentlocker_mailchimp_test',
            nonce: seocontentlocker_mailchimp.nonce
        }, function (response) {
            if (response.success) {
                $result
                    .removeClass('error')
                    .addClass('success')
                    .html('<span class="dashicons dashicons-yes"></span> Conexión OK')
            } else {
                $result
                    .removeClass('success')
                    .addClass('error')
                    .html('<span class="dashicons dashicons-no-alt"></span> Error de conexión')
            }
        }).fail(function () {
            $result.addClass('error').text('Error al conectar con el servidor')
        })
    })
});

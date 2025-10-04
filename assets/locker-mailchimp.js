jQuery(function($){
    $('#mc-test-connection').on('click', function(e){
        e.preventDefault();
        var $btn = $(this);
        var $result = $('#mc-test-result');
        $btn.prop('disabled', true).text('Probando...');
        $result.text('');

        $.post(seocontentlocker_mailchimp.ajax_url, {
            action: 'seocontentlocker_mailchimp_test',
            nonce: seocontentlocker_mailchimp.nonce
        }, function(resp){
            if (resp.success) {
                $result.text(resp.data.message || 'Conexión OK');
            } else {
                $result.text('Error: ' + (resp.data || resp));
            }
        }, 'json').fail(function(){
            $result.text('Error inesperado al probar la conexión.');
        }).always(function(){
            $btn.prop('disabled', false).text('Probar conexión');
        });
    });
});

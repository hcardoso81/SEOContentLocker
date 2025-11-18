// locker-admin.js - scripts admin para SEO Content Locker

jQuery(function ($) {
    // Limpiar URL de parámetros innecesarios
    if (window.history.replaceState) {
        const url = new URL(window.location);
        url.searchParams.delete('_wpnonce');
        url.searchParams.delete('_wp_http_referer');
        url.searchParams.delete('action');
        url.searchParams.delete('action2');
        window.history.replaceState({}, '', url);
    }

    // Confirmación de borrado masivo
    $('#doaction, #doaction2').on('click', function (e) {
        var action = $('select[name="action"]').val() || $('select[name="action2"]').val();
        if (action === 'bulk_delete') {
            if (!confirm('¿Seguro que quieres eliminar los leads seleccionados?')) {
                e.preventDefault();
                return false;
            }
        }
    });

    // Funciones globales para modal de editar fecha
    window.openEditModal = function (leadId, dateInputFormat, dateDisplayFormat, email) {
        $('#edit-lead-id').val(leadId);
        $('#edit-lead-email').text(email);

        $('#current-expire-date-display').text(dateDisplayFormat || 'Sin fecha');

        // Inicializar o setear datepicker
        $("#new-expire-date").datepicker("setDate", dateDisplayFormat);

        $('#edit-date-modal').show();
    };

    window.closeEditModal = function () {
        $('#edit-date-modal').hide();
        $('#edit-date-form')[0].reset();
    };

    // Cerrar modal al hacer clic fuera de él
    $(document).on('click', function (event) {
        var modal = document.getElementById('edit-date-modal');
        if (modal && event.target === modal) {
            window.closeEditModal();
        }
    });

    // Cerrar modal con tecla ESC
    $(document).on('keydown', function (event) {
        if (event.key === 'Escape') {
            var modal = document.getElementById('edit-date-modal');
            if (modal && modal.style.display === 'block') {
                window.closeEditModal();
            }
        }
    });

    // Test conexión MailChimp
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
                    .html('<span class="dashicons dashicons-no-alt"></span> Error de conexión: ' + response.data)
            }
        }).fail(function () {
            $result.addClass('error').text('Error al conectar con el servidor')
        })
    })
});

jQuery(document).ready(function($) {
    // Funktion zum Laden des Inhalts des Posts
    function loadPostContent() {
        $.post(ajax_form_params.ajax_url, { action: 'check_post_content', security: ajax_form_params.nonce }, function(response) {
            if (response.success) {
                var currentContent = $('#form-result').html();
                if (currentContent !== response.data.message) {
                    $('#form-result').html(response.data.message);
                }
            } else {
                console.log('Es gab einen Fehler beim Abrufen des Inhalts.');
            }
        });
    }

    // Laden Sie vorhandenen Inhalt beim Laden der Seite
    loadPostContent();

    // Überprüfen Sie alle 3 Sekunden, ob sich der Inhalt des Posts geändert hat
    setInterval(loadPostContent, 3000);

    $('#ajax-form').on('submit', function(e) {
        e.preventDefault();

        var formData = {
            action: 'handle_ajax_form',
            security: ajax_form_params.nonce,
            name: $('#name').val()
        };

        $.post(ajax_form_params.ajax_url, formData, function(response) {
            if (response.success) {
                $('#form-result').html(response.data.message);
            } else {
                $('#form-result').html('Es gab einen Fehler bei der Verarbeitung.');
            }
        });
    });
});

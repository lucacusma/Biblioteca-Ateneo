$(document).ready(function(){
    $('#select_opera').select2({
        placeholder: "Cerca un'opera o aggiungine una nuova...",
        allowClear: true
    });
    $('#select_editore').select2({
        placeholder: "Cerca un editore...",
        allowClear: true
    });
    $('#select_autori').select2({
        placeholder: "Cerca un autore...",
        allowClear: true
    });

    $('#select_categorie').select2({
        placeholder: "Seleziona categorie...",
        allowClear: true
    });

    $('#select_opera').on('change', function() {
        var valore = $(this).val();
        var divNuova = $('#nuova_opera_div');
        var inputTitolo = $('#input_titolo');

        if(valore === 'nuova') {
            divNuova.slideDown();
            inputTitolo.prop('required', true);
        }
        else {
            divNuova.slideUp();
            inputTitolo.prop('required', false);
        }
    });

    if ($('#select_opera').val() === 'nuova') {
        $('#nuova_opera_div').show();
        $('#input_titolo').prop('required', true);
    }
});
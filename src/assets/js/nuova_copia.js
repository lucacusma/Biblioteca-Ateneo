$(document).ready(function() {
    $('#select_libro').select2({
        placeholder: "Cerca un libro dal catalogo...",
        allowClear: true
    });

    $('#select_sede').select2({
        placeholder: "Seleziona la sede...",
        allowClear: true
    })
})
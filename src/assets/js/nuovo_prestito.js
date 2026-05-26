$(document).ready(function() {
    $('#select_utente').select2({
        placeholder: "Cerca un utente...",
        allowClear: true
    });
    $('#select_copia').select2({
        placeholder: "Cerca un libro...",
        allowClear: true
    })
})
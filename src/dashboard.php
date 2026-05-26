<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }

    require 'include/header.php';
?>

<h1>Dashboard staff</h1>
<p>Benvenuto, <?= htmlspecialchars($_SESSION['nome'])?>.</p>
<hr>

<h3>Prestiti e prenotazioni</h3>
<ul class="dashboard-list">
    <li>
        <strong><a href="nuovo_prestito.php">Registra nuovo prestito</a></strong> <br>
        <small>Uscita libri verso utenti</small>
    </li>
    <li>
        <strong><a href="prestiti.php">Gestisci prestiti</a></strong> <br>
        <small>Rientro libri e controllo scadenze</small>
    </li>
    <li>
        <strong><a href="prenotazioni.php">Gestisci prenotazioni</a></strong> <br>
        <small>Visualizza richieste e code di attesa</small>
    </li>
    <li>
        <strong><a href="storico.php">Storico prestiti</a></strong> <br>
        <small>Archivio di tutti i movimenti passati</small>
    </li>
</ul>

<h3>Gestione Catalogo</h3>
<ul class="dashboard-list">
    <li>
        <strong><a href="libri.php">Cerca nel Catalogo</a></strong> <br>
        <small>Cerca libri, visualizza dettagli e disponibilità</small>
    </li>
    <li>
        <strong><a href="nuovo_libro.php">Inserisci Nuovo Titolo</a></strong> <br>
        <small>Registra una nuova Opera/Edizione</small>
    </li>
    <li>
        <strong><a href="nuova_copia.php">Inserisci Copie Fisiche</a></strong> <br>
        <small>Aggiungi copie a scaffale per libri già a catalogo</small>
    </li>
</ul>

<h3>Amministrazione e anagrafica</h3>
<ul class="dashboard-list">
    <li>
        <strong><a href="utenti.php">Gestione utenti</a></strong> <br>
        <small>Elenco iscritti, attivazione tessere e dettagli</small>
    </li>
    <li>
        <strong><a href="gestione_bibliotecari.php">Gestione Staff</a></strong> <br>
        <small>Crea e gestisci account per i colleghi</small>
    </li>
    <li>
        <strong><a href="profilo.php">Il mio profilo</a></strong> <br>
        <small>Modifica la tua password personale</small>
    </li>
    <li>
        <strong><a href="autori.php">Gestione Autori</a></strong> <br>
        <small>Anagrafica autori</small>
    </li>
    <li>
        <strong><a href="editori.php">Gestione Editori</a></strong> <br>
        <small>Anagrafica case editrici</small>
    </li>
</ul>

<?php
    require 'include/footer.php'
?>
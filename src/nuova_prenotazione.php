<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }

    require 'include/db.php';

    $messaggio = '';

    if (isset($_SESSION['messaggio'])) {
        $messaggio = $_SESSION['messaggio'];
        unset($_SESSION['messaggio']);
    }

    //INSERIMENTO PRENOTAZIONE
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $id_utente = $_POST['id_utente'] ?? null;
        $isbn = $_POST['isbn'] ?? null;

        if($id_utente && $isbn) {
            try {
                $query_check = "SELECT COUNT(*)
                                FROM Prenotazione
                                WHERE Utente = :id
                                AND ISBN = :isbn
                                AND Stato = 'Attiva'";
                $stmt_check = $pdo->prepare($query_check);
                $stmt_check->execute([
                    'id' => $id_utente,
                    'isbn' => $isbn
                ]);

                if($stmt_check->fetchColumn() > 0) {
                    throw new Exception("Questo utente ha già una prenotazione attiva per questa edizione");
                }

                $query_ins = "INSERT INTO Prenotazione(Utente, ISBN, Data, Stato)
                            VALUES(:id, :isbn, CURRENT_DATE, 'Attiva')";
                $stmt_ins = $pdo->prepare($query_ins);
                $stmt_ins->execute([
                    'id' => $id_utente,
                    'isbn' => $isbn
                ]);

                $_SESSION['messaggio'] = "Prenotazione inserita con successo";
                header("Location: nuova_prenotazione.php");
                exit;
            }
            catch(Exception $e) {
                $messaggio = 'Errore: ' . $e->getMessage();
            }
        }
        else {
            $messaggio = "Seleziona un utente e un libro per procedere";
        }
    }

    $stmt_utenti = $pdo->query("SELECT ID_Utente, Nome, Cognome, Codice_Tessera
                                FROM Utente
                                WHERE Stato = 'Attivo'
                                ORDER BY Cognome ASC, Nome ASC");
    $lista_utenti = $stmt_utenti->fetchAll();

    $query_libri = "SELECT E.ISBN, O.Titolo, E.Anno_Edizione,
                    GROUP_CONCAT(CONCAT(Autore.Nome, ' ', Autore.Cognome) SEPARATOR ', ') as Autori_String
                    FROM Edizione E
                    JOIN Opera O ON E.Opera = O.ID_Opera
                    JOIN Scrittura ON O.ID_Opera = Scrittura.Opera
                    JOIN Autore ON Scrittura.Autore = Autore.ID_Autore
                    GROUP BY E.ISBN
                    ORDER BY O.Titolo ASC, E.Anno_Edizione ASC";
    $stmt_libri = $pdo->query($query_libri);
    $lista_libri = $stmt_libri->fetchAll();

    require 'include/header.php';
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<h1>Nuova Prenotazione</h1>
<a href="dashboard.php">Torna alla Dashboard</a> | <a href="prenotazioni.php">Gestione Prenotazioni</a>
<hr>

<?php if ($messaggio): ?>
    <p><strong><?= htmlspecialchars($messaggio) ?></strong></p>
<?php endif; ?>

<form method="post">
    <h3>1. Seleziona utente</h3>
    <label>Cerca per Nome o Tessera:</label>
    <select name="id_utente" id="select_utente" required>
        <option></option>
        <?php foreach($lista_utenti as $u):?>
            <option value="<?=$u['ID_Utente']?>">
                <?=htmlspecialchars($u['Cognome'] . ' ' . $u['Nome'])?> (Tessera: <?=htmlspecialchars($u['Codice_Tessera'])?>)
            </option>
        <?php endforeach;?>
    </select>

    <br><hr>
    <h3>2. Seleziona libro</h3>
    <label>Cerca per Titolo, Autore o ISBN:</label>
    <select name="isbn" id="select_libro" required>
        <option></option>
        <?php foreach($lista_libri as $l):?>
            <option value="<?=$l['ISBN']?>">
                <?=htmlspecialchars($l['Titolo'])?> (di <?=htmlspecialchars($l['Autori_String'])?>) - ISBN: <?=htmlspecialchars($l['ISBN'])?> <small> - (<?=htmlspecialchars($l['Anno_Edizione'])?>)</small>
            </option>
        <?php endforeach;?>
    </select>
    <br><br><br>
    <button type="submit">Conferma prenotazione</button>
</form>

<script src="assets/js/nuova_prenotazione.js"></script>

<?php
    require 'include/footer.php';
?>
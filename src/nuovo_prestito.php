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

    if (isset($_SESSION['feedback_prestito'])) {
        $messaggio = $_SESSION['feedback_prestito'];
        unset($_SESSION['feedback_prestito']);
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $id_utente = $_POST['id_utente'];
        $id_copia = $_POST['id_copia'];

        $id_bibliotecario = $_SESSION['id_bibliotecario'];

        $data_inizio = date('Y-m-d');
        $data_fine = date('Y-m-d', strtotime('+30 days'));

        try {
            $stmt_check = $pdo->prepare("SELECT Stato FROM Copia WHERE ID_Copia = :id");
            $stmt_check->execute(['id' => $id_copia]);

            if($stmt_check->fetchColumn() !== 'Disponibile') {
                throw new Exception("Questa copia non è più disponibile");
            }

            $query_prestito = 'INSERT INTO Prestito(Utente, Copia, Bibliotecario, Data_Inizio, Data_Fine)
                                VALUES(:utente, :copia, :bibliotecario, :data_inizio, :data_fine)';

            $stmt_prestito = $pdo->prepare($query_prestito);
            $stmt_prestito->execute([
                'utente' => $id_utente,
                'copia' => $id_copia,
                'bibliotecario' => $id_bibliotecario,
                'data_inizio' => $data_inizio,
                'data_fine' => $data_fine
            ]);

            $query_update = "UPDATE Copia SET Stato = 'In Prestito' WHERE ID_Copia = :copia";

            $stmt_update = $pdo->prepare($query_update);
            $stmt_update->execute(['copia' => $id_copia]);

            $_SESSION['feedback_prestito'] = "Prestito registrato con successo!";

            header("Location: nuovo_prestito.php");
            exit;
        }
        catch(PDOException $e) {
            $messaggio = 'Errore Database: ' . $e->getMessage();
        }

    }

    $query_utenti = "SELECT ID_Utente, Nome, Cognome, Codice_Tessera
                    FROM Utente
                    WHERE Stato = 'Attivo'
                    ORDER BY Cognome ASC, Nome ASC";
    $stmt_utenti = $pdo->query($query_utenti);
    $lista_utenti = $stmt_utenti->fetchAll();

    $query_copie = "SELECT C.ID_Copia, O.Titolo, E.ISBN, E.Anno_Edizione, Sede.Nome AS Nome_Sede,
                    GROUP_CONCAT(CONCAT(Autore.Nome, ' ', Autore.Cognome) SEPARATOR ', ') as Autori_String
                    FROM Copia C
                    JOIN Edizione E ON C.ISBN = E.ISBN
                    JOIN Opera O ON E.Opera = O.ID_Opera
                    JOIN Sede ON C.Sede = Sede.ID_Sede
                    JOIN Scrittura ON O.ID_Opera = Scrittura.Opera
                    JOIN Autore ON Scrittura.Autore = Autore.ID_Autore
                    WHERE C.Stato = 'Disponibile'
                    GROUP BY C.ID_Copia
                    ORDER BY O.Titolo ASC";
    $stmt_copie = $pdo->query($query_copie);
    $lista_copie = $stmt_copie->fetchAll();

    require 'include/header.php';
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<h1>Registra Nuovo Prestito</h1>
<a href="dashboard.php">Indietro alla Dashboard</a>
<hr>

<?php if ($messaggio != ''): ?>
    <p><strong><?= $messaggio ?></strong></p>
    <hr>
<?php endif; ?>

<form method="POST">
    <h3>1. Seleziona Utente</h3>
    <label>Cerca Utenti per Nome o Tessera</label>
    <select name="id_utente" id="select_utente" required>
        <option></option>
        <?php foreach($lista_utenti as $u): ?>
            <option value="<?=$u['ID_Utente']?>">
                <?=htmlspecialchars($u['Nome'] . ' '. $u['Cognome'] . ' (Tessera: ' . $u['Codice_Tessera'] . ')')?>
            </option>
        <?endforeach;?>
    </select>

    <br><hr>

    <h3>2. Seleziona Libro</h3>
    <label>Cerca copie per Nome o ISBN</label>
    <select name="id_copia" id="select_copia" required>
        <option></option> <?php foreach ($lista_copie as $c): ?>
            <option value="<?= $c['ID_Copia'] ?>">
                [#<?= $c['ID_Copia'] ?>] <?= htmlspecialchars($c['Titolo']) ?> (<?= htmlspecialchars($c['Anno_Edizione']) ?>) di <?= htmlspecialchars($c['Autori_String']) ?> - Sede: <?= htmlspecialchars($c['Nome_Sede']) ?> (ISBN: <?= htmlspecialchars($c['ISBN']) ?>)
            </option>
        <?php endforeach; ?>
    </select>

    <br><br>
    
    <input type="submit" value="REGISTRA PRESTITO">

</form>

<script src="assets/js/nuovo_prestito.js"></script>

<?php
    require 'include/footer.php';
?>

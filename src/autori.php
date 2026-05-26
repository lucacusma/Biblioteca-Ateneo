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

    //Inserimento
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $nome = $_POST['nome'];
        $cognome = $_POST['cognome'];

        $data_nascita = !empty($_POST['nascita']) ? $_POST['nascita'] : NULL;
        $data_morte = !empty($_POST['morte']) ? $_POST['morte'] : NULL;

        try {
            $query = "INSERT INTO Autore(Nome, Cognome, Data_Nascita, Data_Morte)
                    VALUES (:nome, :cognome, :nascita, :morte)";

            $stmt = $pdo->prepare($query);
            $stmt->execute([
                'nome' => $nome,
                'cognome' => $cognome,
                'nascita' => $data_nascita,
                'morte' => $data_morte
            ]);

            $_SESSION['messaggio'] = "Autore inserito con successo";
            header('Location: autori.php');
            exit;
        }
        catch(Exception $e){
            $messaggio = "Errore: " . $e->getMessage();
        }
    }

    //Visualizzazione
    $stmt_elenco = $pdo->query("SELECT * FROM Autore ORDER BY Nome ASC, Cognome ASC");

    require 'include/header.php';
?>

<h1>Gestione Autori</h1>
<a href="dashboard.php">Torna alla Dashboard</a> | <a href="editori.php">Gestione Editori</a>

<hr>

<?php if ($messaggio): ?>
    <p><strong><?= htmlspecialchars($messaggio) ?></strong></p>
<?php endif; ?>

<h3>Aggiungi nuovo autore</h3>
<form method="post">
    <label for="nome">Nome:</label>
    <input type="text" name="nome" required> <br><br>

    <label for="cognome">Cognome:</label>
    <input type="text" name="cognome" required> <br><br>

    <label for="nascita">Data di nascita:</label>
    <input type="date" name="nascita"> <br><br>

    <label for="morte">Data di morte:</label>
    <input type="date" name="morte"> <br>
    <small>Lasciare vuoto se l'autore è ancora in vita</small><br><br>

    <button type="submit">Inserisci autore</button>
</form>

<hr>

<h3>Elenco Autori</h3>

<?php if($stmt_elenco->rowCount() > 0):?>
    <div class="table-wrapper">
        <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Data di nascita</th>
                        <th>Data di morte</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $stmt_elenco->fetch()):?>
                        <tr>
                            <td><?=htmlspecialchars($row['Nome'] . ' ' . $row['Cognome'])?></td>
                            <td><?=$row['Data_Nascita'] ? htmlspecialchars(date('d-m-Y', strtotime($row['Data_Nascita']))) : '-'?></td>
                            <td><?=$row['Data_Morte'] ? htmlspecialchars(date('d-m-Y', strtotime($row['Data_Morte']))) : '-'?></td>
                        </tr>
                    <?php endwhile;?>
                </tbody>
            </table>
    </div>
<?php else:?>
    <p>Nessun autore presente nel database</p>
<?php endif;?>
<?php
    require 'include/footer.php';
?>
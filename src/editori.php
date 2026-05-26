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

        $sede = !empty($_POST['sede']) ? $_POST['sede'] : NULL;
        $sito = !empty($_POST['sito']) ? $_POST['sito'] : NULL;

        try {
            $query = "INSERT INTO Editore(Nome, Sede_Legale, Sito_Web)
                    VALUES (:nome, :sede, :sito)";

            $stmt = $pdo->prepare($query);
            $stmt->execute([
                'nome' => $nome,
                'sede' => $sede,
                'sito' => $sito
            ]);

            $_SESSION['messaggio'] = "Editore inserito con successo";
            header('Location: editori.php');
            exit;
        }
        catch(Exception $e){
            $messaggio = "Errore: " . $e->getMessage();
        }
    }

    //Visualizzazione
    $stmt_elenco = $pdo->query("SELECT * FROM Editore ORDER BY Nome");

    require 'include/header.php';
?>

<h1>Gestione Editori</h1>
<a href="dashboard.php">Torna alla Dashboard</a> | <a href="autori.php">Gestione Autori</a>

<hr>

<?php if ($messaggio): ?>
    <p><strong><?= htmlspecialchars($messaggio) ?></strong></p>
<?php endif; ?>

<h3>Aggiungi nuovo editore</h3>
<form method="post">
    <label for="nome">Nome:</label>
    <input type="text" name="nome" required> <br><br>

    <label for="sede">Sede legale:</label>
    <input type="text" name="sede"> <br><br>

    <label for="sito">Sito web:</label>
    <input type="text" name="sito"> <br><br>

    <button type="submit">Inserisci editore</button>
</form>

<hr>

<h3>Elenco Editori</h3>

<?php if($stmt_elenco->rowCount() > 0):?>
    <div class="table-wrapper">
        <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Sede legale</th>
                        <th>Sito web</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $stmt_elenco->fetch()):?>
                        <tr>
                            <td><?=htmlspecialchars($row['Nome'])?></td>
                            <td><?=$row['Sede_Legale'] ? htmlspecialchars($row['Sede_Legale']) : '-'?></td>
                            <td>
                                <?php if($row['Sito_Web'] != NULL):?>
                                    <a target="_blank" href="https://<?=htmlspecialchars($row['Sito_Web'])?>"><?=htmlspecialchars($row['Sito_Web'])?></a>
                                <?php endif;?>
                            </td>
                        </tr>
                    <?php endwhile;?>
                </tbody>
            </table>
    </div>
<?php else:?>
    <p>Nessun editore presente nel database</p>
<?php endif;?>
<?php
    require 'include/footer.php';
?>
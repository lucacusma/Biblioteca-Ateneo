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

    $isbn_default = $_GET['isbn'] ?? '';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $isbn = $_POST['isbn'];
        $id_sede = $_POST['id_sede'];

        $num_copie = isset($_POST['num_copie']) ? (int)$_POST['num_copie'] : 1;

        if($num_copie < 1) $num_copie = 1;

        try {
            $stmt_check = $pdo->prepare("SELECT ISBN FROM Edizione WHERE ISBN = :isbn");
            $stmt_check->execute(['isbn' => $isbn]);

            if($stmt_check->rowCount() === 0){
                throw new Exception("ISBN non trovato nel catalogo. Inserisci prima il libro");
            }

            $pdo->beginTransaction();

            $query = "INSERT INTO Copia(ISBN, Sede) VALUES(:isbn, :id_sede)";
            $stmt = $pdo->prepare($query);
            $id_generati = [];

            for($i = 0; $i < $num_copie; $i++){
                $stmt->execute([
                    'isbn' => $isbn,
                    'id_sede' => $id_sede
                ]);
                $id_generati[] = $pdo->lastInsertID();
            }

            $pdo->commit();

            $msg_id = implode(', ', $id_generati);

            $_SESSION['messaggio'] = "Registrate con successo $num_copie copie. <br><strong>ID da etichettare: " . $msg_id . "</strong>";

            header("Location: nuova_copia.php?isbn=" . urlencode($isbn));
            exit;
        }
        catch(Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $messaggio = "Errore: " . $e->getMessage();
        }
    }

    $stmt_sedi = $pdo->query("SELECT ID_Sede, Nome FROM Sede ORDER BY Nome ASC");
    $sedi = $stmt_sedi->fetchAll();

    $stmt_libri = $pdo->query("SELECT Edizione.ISBN, Edizione.Anno_Edizione, Opera.Titolo, Editore.Nome AS NomeEditore
                                FROM Edizione
                                JOIN Opera ON Edizione.Opera = Opera.ID_Opera
                                JOIN Editore ON Edizione.Editore = Editore.ID_Editore
                                ORDER BY Opera.Titolo ASC, Edizione.Anno_Edizione ASC");
    $lista_libri = $stmt_libri->fetchAll();

    require 'include/header.php';
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<h1>Nuova Copia Fisica</h1>
<a href="dashboard.php">Torna alla Dashboard</a> | <a href="libri.php">Catalogo Libri</a>

<hr>

<?php if ($messaggio): ?>
    <p><strong><?= $messaggio ?></strong></p>
<?php endif; ?>

<form method="post">
    <h3>Dati acquisizione</h3>

    <label>Seleziona Libro (Titolo o ISBN):</label><br>
    <select name="isbn" id="select_libro" required>
        <option></option>
        <?php foreach($lista_libri as $l):?>
            <option value="<?=$l['ISBN']?>" <?=($isbn_default == $l['ISBN'])? 'selected' : ''?>>
                <?= htmlspecialchars($l['Titolo']) ?> (<?= htmlspecialchars($l['Anno_Edizione']) ?>) - <?=htmlspecialchars($l['NomeEditore'])?> - ISBN: <?= htmlspecialchars($l['ISBN']) ?>
            </option>
        <?php endforeach;?>
    </select>

    <br><br>

    <select name="id_sede" id="select_sede" required>
        <option value="" selected disabled>-- Seleziona Sede --</option>
        <?php foreach($sedi as $s):?>
            <option value="<?=$s['ID_Sede']?>"><?=$s['Nome']?></option>
        <?php endforeach;?>
    </select>

    <br><br>

    <label>Numero di copie da aggiungere:</label><br>
    <input type="number" name="num_copie" value="1" min="1" max="50">
    <br>
    <small>Verranno generati ID univoci per ogni copia.</small>

    <br><br>

    <button type="submit">Registra copia e genera ID</button>
</form>

<script src="assets/js/nuova_copia.js"></script>

<?php
    require 'include/footer.php';
?>
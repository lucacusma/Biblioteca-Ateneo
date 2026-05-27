<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }

    require 'include/db.php';

    $messaggio ='';

    if (isset($_SESSION['messaggio'])) {
        $messaggio = $_SESSION['messaggio'];
        unset($_SESSION['messaggio']);
    }

    //Inserimento dati
    if ($_SERVER["REQUEST_METHOD"] == "POST"){
        $id_editore = $_POST['id_editore'] ?? null;
        $isbn = $_POST['isbn'];
        $anno_originale = !empty($_POST['anno_originale']) ? $_POST['anno_originale'] : null;
        $lingua_originale = !empty($_POST['lingua_originale']) ? ucfirst(strtolower(trim($_POST['lingua_originale']))) : null;
        $pagine = !empty($_POST['pagine']) ? $_POST['pagine'] : null;

        $scelta_opera = $_POST['scelta_opera'] ?? null;

        $autori_selezionati = $_POST['autori'] ?? [];
        $categorie_selezionate = $_POST['categorie'] ?? [];

        $titolo_nuovo = $_POST['titolo_nuovo'] ?? '';
        $anno_edizione = !empty($_POST['anno_edizione']) ? $_POST['anno_edizione'] : null;
        $lingua_edizione = !empty($_POST['lingua_edizione']) ? ucfirst(strtolower(trim($_POST['lingua_edizione']))) : null;

        if(!$id_editore){
            $messaggio = "Errore: devi selezionare un editore";
        }
        elseif(!$scelta_opera){
            $messaggio = "Errore: devi selezionare o aggiungere un'opera";
        }
        else{
            try{
                $pdo->beginTransaction();

                $id_opera_finale = null;

                //creazione nuova opera
                if($scelta_opera === 'nuova') {
                    if(empty($titolo_nuovo) || empty($autori_selezionati)) {
                        throw new Exception("Per inserire una nuova opera devi inserire un titolo e selezionare un autore");
                    }

                    $query_opera = "INSERT INTO Opera(Titolo, Lingua_Originale, Anno_Pubblicazione)
                                    VALUES(:titolo, :lingua, :anno)";
                    $stmt_opera = $pdo->prepare($query_opera);
                    $stmt_opera->execute([
                        'titolo' => $titolo_nuovo, 
                        'lingua' => $lingua_originale, 
                        'anno' => $anno_originale
                    ]);

                    $id_opera_finale = $pdo->lastInsertID();

                    $query_scrittura = "INSERT INTO Scrittura(Opera, Autore) VALUES(:opera, :autore)";
                    $stmt_scrittura = $pdo->prepare($query_scrittura);

                    foreach($autori_selezionati as $id_autore) {
                        $stmt_scrittura->execute([
                            'opera' => $id_opera_finale,
                            'autore' => $id_autore
                        ]);
                    }

                    if(!empty($categorie_selezionate)) {
                        $query_class = "INSERT INTO Classificazione(Opera, Categoria) VALUES (:opera, :categoria)";
                        $stmt_class = $pdo->prepare($query_class);

                        foreach($categorie_selezionate as $cat){
                            $stmt_class->execute([
                                'opera' => $id_opera_finale,
                                'categoria' => $cat
                            ]);
                        }
                    }
                }
                else {
                    $id_opera_finale = $scelta_opera;
                }

                $query_edizione = "INSERT INTO Edizione(ISBN, Opera, Editore, Lingua_Pubblicazione, Anno_Edizione, Pagine)
                                    VALUES(:isbn, :opera, :editore, :lingua, :anno, :pagine)";
                $stmt_edizione = $pdo->prepare($query_edizione);
                $stmt_edizione->execute([
                    'isbn' => $isbn,
                    'opera' => $id_opera_finale,
                    'editore' => $id_editore,
                    'lingua' => $lingua_edizione,
                    'anno' => $anno_edizione,
                    'pagine' => $pagine
                ]);

                $pdo->commit();

                $_SESSION['messaggio'] = "Libro inserito nel catalogo";
                header('Location: nuovo_libro.php');
                exit;
            }
            catch (Exception $e) {
                if($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $messaggio = 'Messaggio: ' . $e->getMessage();
            }
        }
    }

    //Caricamento autori
    $stmt_autori = $pdo->query("SELECT ID_Autore, Nome, Cognome FROM Autore ORDER BY Cognome ASC, Nome ASC");
    $lista_autori = $stmt_autori->fetchAll();

    //Caricamento opere
    $stmt_opere = $pdo->query("SELECT O.ID_Opera, O.Titolo,
                                GROUP_CONCAT(CONCAT(A.Nome, ' ', A.Cognome) SEPARATOR ', ') AS Autori_String
                                FROM Opera O
                                LEFT JOIN Scrittura S ON O.ID_Opera = S.Opera
                                LEFT JOIN Autore A ON S.Autore = A.ID_Autore
                                GROUP BY O.ID_Opera
                                ORDER BY O.Titolo ASC");
    $lista_opere = $stmt_opere->fetchAll();

    //Caricamento editori
    $stmt_editori = $pdo->query("SELECT ID_Editore, Nome FROM Editore ORDER BY Nome ASC");
    $lista_editori = $stmt_editori->fetchAll();

    //Caricamento categorie
    $stmt_cat = $pdo->query("SELECT ID_Categoria, Nome, Macro_Categoria FROM Categoria ORDER BY Nome ASC");
    $lista_categorie = $stmt_cat->fetchAll();

    //Suggerimento lingue
    $stmt_lingue_ed = $pdo->query("SELECT DISTINCT Lingua_Pubblicazione FROM Edizione WHERE Lingua_Pubblicazione IS NOT NULL AND Lingua_Pubblicazione != '' ORDER BY Lingua_Pubblicazione ASC");
    $lista_lingue_ed = $stmt_lingue_ed->fetchAll(PDO::FETCH_COLUMN);

    $stmt_lingue_orig = $pdo->query("SELECT DISTINCT Lingua_Originale FROM Opera WHERE Lingua_Originale IS NOT NULL AND Lingua_Originale != '' ORDER BY Lingua_Originale ASC");
    $lista_lingue_orig = $stmt_lingue_orig->fetchAll(PDO::FETCH_COLUMN);

    require 'include/header.php';
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<h1>Inserisci Nuovo Libro</h1>
<a href="dashboard.php">Torna alla Dashboard</a> | <a href="libri.php">Catalogo Libri</a>

<hr>

<?php if ($messaggio): ?>
    <p><strong><?= htmlspecialchars($messaggio) ?></strong></p>
<?php endif; ?>

<form method="post">
    <h3>1. Seleziona Opera</h3>
    <label>Seleziona Opera esistente o creane una nuova</label> <br>

    <select name="scelta_opera" id="select_opera" class="js-select-opera" required>
        <option></option>
        <option value="nuova">AGGIUNGI NUOVA OPERA</option>
        <?php foreach($lista_opere as $o):?>
            <option value="<?=$o['ID_Opera']?>">
                <?= htmlspecialchars($o['Titolo'])?> <small>(<?=htmlspecialchars($o['Autori_String'])?>)</small>
            </option>
        <?php endforeach;?>
    </select>

    <br>

    <div id="nuova_opera_div" style="display: none;">
        <h4>Dati nuova opera:</h4>

        <label for="input_titolo">Titolo:</label>
        <input type="text" name="titolo_nuovo" id="input_titolo"><br><br>

        <label for="lingua_originale">Lingua Originale:</label><br>
        <input type="text" name="lingua_originale" id="lingua_originale" list="suggerimenti_lingue_orig" autocomplete="off">
        <datalist id="suggerimenti_lingue_orig">
            <?php foreach($lista_lingue_orig as $lingua): ?>
                <option value="<?= htmlspecialchars($lingua) ?>"></option>
            <?php endforeach; ?>
        </datalist>
        <br><br>

        <label for="anno_originale">Anno Pubblicazione Originale:</label>
        <input type="number" name="anno_originale" id="anno_originale" max=<?=date('Y')?>><br><br>

        <label>Seleziona autori:</label><br>
        <select class="js-autori-multiple" name="autori[]" multiple="multiple" id="select_autori">
            <?php foreach($lista_autori as $a):?>
                <option value="<?= $a['ID_Autore']?>">
                    <?= htmlspecialchars($a['Nome'] . ' ' . $a['Cognome']) ?>
                </option>
            <?php endforeach;?>
        </select>

        <br><br>

        <label>Classificazione (Categorie):</label><br>
        <select class="js-categorie-multiple" name="categorie[]" multiple="multiple" id="select_categorie">
            <?php foreach($lista_categorie as $c):?>
                <option value="<?= $c['ID_Categoria']?>">
                    <?= htmlspecialchars($c['Nome']) ?>
                </option>
            <?php endforeach;?>
        </select>
        <br><small>Selezionando una sottocategoria, verranno automaticamente selezionate le macrocategorie</small>
    </div>

    <hr>

    <h3>2. Seleziona Editore</h3>
    <select name="id_editore" id="select_editore" class="js-select-editore" required>
        <option></option>
        <?php foreach($lista_editori as $e):?>
            <option value="<?=$e['ID_Editore']?>">
                <?= htmlspecialchars($e['Nome'])?>
            </option>
        <?php endforeach;?>
    </select>

    <hr>

    <h3>Dati Edizione</h3>

    <label for="isbn">Codice ISBN:</label><br>
    <input type="text" name="isbn" id="isbn" required minlength="10" maxlength="13"><br><br>

    <label for="lingua_edizione">Lingua Edizione:</label><br>
    <input type="text" name="lingua_edizione" id="lingua_edizione" list="suggerimenti_lingue_ed" autocomplete="off">
    <datalist id="suggerimenti_lingue_ed">
        <?php foreach($lista_lingue_ed as $lingua): ?>
            <option value="<?= htmlspecialchars($lingua) ?>"></option>
        <?php endforeach; ?>
    </datalist>
    <br><br>

    <label for="anno_edizione">Anno Edizione:</label><br>
    <input type="number" name="anno_edizione" id="anno_edizione" min="1500" max="<?= date('Y') ?>"><br><br>

    <label for="pagine">Numero pagine:</label><br>
    <input type="number" name="pagine" id="pagine"><br><br>

    <button type="submit">Salva Libro</button>
</form>

<script src="assets/js/nuovo_libro.js"></script>

<?php
    require 'include/footer.php';
?>
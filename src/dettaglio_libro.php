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
    if (isset($_SESSION['messaggio_flash'])) {
        $messaggio = $_SESSION['messaggio_flash'];
        unset($_SESSION['messaggio_flash']);
    }

    $isbn = $_GET['isbn'] ?? null;
    if(!$isbn) {
        header('Location: libri.php');
        exit;
    }

    //gestiona azioni
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $msg_output = '';

        //Cambio di stato
        if (isset($_POST['azione']) && $_POST['azione'] === 'aggiorna_stato'){
            $id_copia = $_POST['id_copia'];
            $nuovo_stato = $_POST['nuovo_stato'];

            try {
                $stmt_check = $pdo->prepare("SELECT Stato FROM Copia WHERE ID_Copia = :id");
                $stmt_check->execute(['id' => $id_copia]);
                $stato_attuale = $stmt_check->fetchColumn();

                if($stato_attuale === 'In prestito'){
                    throw new Exception("Copia attualmente in prestito. Impossibile modificarne lo stato.");
                }

                $query_update = "UPDATE Copia SET Stato = :stato WHERE ID_Copia = :id";
                $stmt_update = $pdo->prepare($query_update);
                $stmt_update->execute([
                    'stato' => $nuovo_stato,
                    'id' => $id_copia
                ]);

                $msg_output = 'Stato copia ' . $id_copia . ' aggiornato a <strong>' . $nuovo_stato . '</strong>';
            }
            catch(Exception $e) {
                $msg_output = 'Errore: ' . $e->getMessage();
            }
        }

        if(isset($_POST['azione']) && $_POST['azione'] === 'elimina_copia') {
            $id_copia = $_POST['id_copia'];

            try {
                $stmt_storico = $pdo->prepare("SELECT COUNT(*) FROM Prestito WHERE Copia = :id");
                $stmt_storico->execute(['id' => $id_copia]);
                $storico = $stmt_storico->fetchColumn() > 0;

                if($storico) {
                    $stmt_soft = $pdo->prepare("UPDATE Copia SET Stato = 'Dismesso' WHERE ID_Copia = :id");
                    $stmt_soft->execute(['id' => $id_copia]);
                    $msg_output = "La copia #$id_copia ha uno storico prestiti. È stata impostata come <strong>DISMESSO</strong>";
                }
                else{
                    $stmt_del = $pdo->prepare("DELETE FROM Copia WHERE ID_Copia = :id");
                    $stmt_del->execute(['id' => $id_copia]);
                    $msg_output = "Copia #$id_copia eliminata definitivamente";
                }
            }
            catch(Exception $e) {
                $msg_output = 'Errore eliminazione: ' . $e->getMessage();
            }
        }
        if ($msg_output) {
            $_SESSION['messaggio_flash'] = $msg_output;
        }

        header("Location: dettaglio_libro.php?isbn=" . urlencode($isbn));
        exit;
    }

    //recupero dati libro
    $query_libro = "SELECT  O.ID_Opera, O.Titolo, O.Anno_Pubblicazione, O.Lingua_Originale,
                            E.ISBN, E.Anno_Edizione, E.Pagine, E.Lingua_Pubblicazione,
                            Editore.Nome AS Editore,
                            GROUP_CONCAT(DISTINCT CONCAT(A.Nome, ' ', A.Cognome) SEPARATOR ', ') AS Autori
                    FROM Edizione E
                    JOIN Opera O ON E.Opera = O.ID_Opera
                    JOIN Editore ON E.Editore = Editore.ID_Editore
                    LEFT JOIN Scrittura ON O.ID_Opera = Scrittura.Opera
                    LEFT JOIN Autore A ON Scrittura.Autore = A.ID_Autore
                    WHERE E.ISBN = :isbn
                    GROUP BY E.ISBN";

    $stmt_libro = $pdo->prepare($query_libro);
    $stmt_libro->execute(['isbn' => $isbn]);
    $libro = $stmt_libro->fetch();

    if(!$libro){
        echo 'Libro non trovato';
        exit;
    }

    $stmt_cat = $pdo->prepare("SELECT Categoria.Nome
                                FROM Categoria
                                JOIN Classificazione ON Categoria.ID_Categoria = Classificazione.Categoria
                                WHERE Classificazione.Opera = :id
                                ORDER BY Categoria.Nome ASC");
    $stmt_cat->execute(['id' => $libro['ID_Opera']]);
    $categorie = $stmt_cat->fetchAll(PDO::FETCH_COLUMN);

    $query_copie = "SELECT Copia.ID_Copia, Copia.Stato, Copia.Data_Acquisizione, Sede.Nome AS Sede
                    FROM Copia
                    JOIN Sede ON Sede.ID_Sede = Copia.Sede
                    WHERE Copia.ISBN = :isbn
                    ORDER BY FIELD(Copia.Stato, 'Disponibile', 'In prestito', 'Restauro', 'Smarrito', 'Dismesso'), Copia.ID_Copia ASC";
    $stmt_copie = $pdo->prepare($query_copie);
    $stmt_copie->execute(['isbn' => $isbn]);

    require 'include/header.php';
?>

<h1>Dettaglio Libro</h1>
<a href="dashboard.php">Dashboard</a> | <a href="libri.php">Catalogo</a> | <a href="nuova_copia.php?isbn=<?= $isbn ?>">Aggiungi Copia</a>

<hr>

<?php if ($messaggio): ?>
    <p><strong><?= $messaggio ?></strong></p>
<?php endif; ?>

<h2><?=htmlspecialchars($libro['Titolo'])?></h2>

<p>
    <strong>Autori:</strong> <?=htmlspecialchars($libro['Autori'])?> <br>

    <strong>Categorie:</strong>
    <?php if(!empty($categorie)):?>
        <?php foreach($categorie as $c):?>
                <?= htmlspecialchars($c) ?>
        <?php endforeach;?>
    <?php else: ?>
        <em>Nessuna categoria specificata</em>
    <?php endif; ?>
    <br>

    <strong>Editore:</strong> <?=htmlspecialchars($libro['Editore'] ?? '-')?> <br>
    <strong>ISBN:</strong> <?=htmlspecialchars($libro['ISBN'] ?? '-')?> <br>
    <strong>Pagine:</strong> <?=htmlspecialchars($libro['Pagine'] ?? '-')?> <br>
    <strong>Lingua Originale:</strong> <?=htmlspecialchars($libro['Lingua_Originale'] ?? '-')?> <br>
    <strong>Lingua Edizione:</strong> <?=htmlspecialchars($libro['Lingua_Pubblicazione'] ?? '-')?> <br>
    <strong>Anno di pubblicazione originale:</strong> <?=htmlspecialchars($libro['Anno_Pubblicazione'] ?? '-')?> <br>
    <strong>Anno Edizione:</strong> <?=htmlspecialchars($libro['Anno_Edizione'] ?? '-')?> <br>
</p>

<hr>

<h3>Gestione copie fisiche</h3>

<?php if($stmt_copie->rowCount() > 0):?>
    <div class="table-wrapper">
        <table>
                <thead>
                    <tr>
                        <th>ID Copia</th>
                        <th>Sede</th>
                        <th>Data Acquisto</th>
                        <th>Stato</th>
                        <th>Modifica Stato</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($copia = $stmt_copie->fetch()):?>
                        <tr>
                            <td><strong>#<?=htmlspecialchars($copia['ID_Copia'])?></strong></td>
                            <td><?=htmlspecialchars($copia['Sede'])?></td>
                            <td><?=htmlspecialchars($copia['Data_Acquisizione'])?></td>
                            <td><?=htmlspecialchars($copia['Stato'])?></td>
                            <td>
                                <?php if($copia['Stato'] !== 'In prestito' && $copia['Stato'] !== 'Dismesso'): ?>
                                    <form method="post">
                                        <input type="hidden" name="azione" value="aggiorna_stato">
                                        <input type="hidden" name="id_copia" value="<?=$copia['ID_Copia']?>">
                                        <select name="nuovo_stato" onchange="this.form.submit()">
                                            <option value="Disponibile" <?= $copia['Stato']=='Disponibile' ? 'selected' : '' ?>>Disponibile</option>
                                            <option value="Restauro" <?= $copia['Stato']=='Restauro' ? 'selected' : '' ?>>Restauro</option>
                                            <option value="Smarrito" <?= $copia['Stato']=='Smarrito' ? 'selected' : '' ?>>Smarrito</option>
                                            <option value="Dismesso" <?= $copia['Stato']=='Dismesso' ? 'selected' : '' ?>>Dismesso</option>
                                        </select>
                                    </form>
                                <?php elseif($copia['Stato'] === 'In prestito'):?>
                                    <small><em>In prestito</em></small>
                                <?php else:?>
                                    <small>-</small>
                                <?php endif;?>
                            </td>
                            <td>
                                <?php if($copia['Stato'] !== 'In prestito' && $copia['Stato'] !== 'Dismesso'):?>
                                    <form method="post" onsubmit="return confirm('Sei sicuro? Se la copia ha uno storico, verrà segnata come DISMESSO, altrimenti verrà eliminata.');">
                                        <input type="hidden" name="azione" value="elimina_copia">
                                        <input type="hidden" name="id_copia" value="<?= $copia['ID_Copia'] ?>">
                                        <button type="submit">X</button>
                                    </form>
                                <?php elseif ($copia['Stato'] === 'Dismesso'): ?>
                                    <small><em>Archiviato</em></small>
                                <?php endif;?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
    </div>
<?php else:?>
    <p>Non ci sono copie fisiche registrate per questo libro</p>
    <a href="nuova_copia.php?isbn=<?= $libro['ISBN'] ?>"><button>Aggiungi copia</button></a>
<?php endif; ?>

<?php
    require 'include/footer.php';
?>
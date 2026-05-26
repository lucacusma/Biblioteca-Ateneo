<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }

    require 'include/db.php';

    $search_utente = $_GET['s_utente'] ?? '';
    $search_libro = $_GET['s_libro'] ?? '';

    $params = [];

    $query =    "SELECT Prestito.Data_Inizio, Prestito.Data_Fine, Prestito.Data_Restituzione,
                        Utente.Nome AS Nome_Utente, Utente.Cognome AS Cognome_Utente, Utente.Codice_Tessera AS Tessera,
                        Opera.Titolo, Edizione.ISBN, Bibliotecario.Nome AS Nome_Biblio, Bibliotecario.Cognome AS Cognome_Biblio
                FROM Prestito
                JOIN Utente ON Prestito.Utente = Utente.ID_Utente
                JOIN Copia ON Prestito.Copia = Copia.ID_Copia
                JOIN Edizione ON Copia.ISBN = Edizione.ISBN
                JOIN Opera ON Edizione.Opera = Opera.ID_Opera
                JOIN Bibliotecario ON Prestito.Bibliotecario = Bibliotecario.ID_Bibliotecario
                WHERE Data_Restituzione IS NOT NULL";

    if($search_utente != '') {
        $query .= " AND (Utente.Nome LIKE ? OR Utente.Cognome LIKE ? OR Utente.Codice_Tessera LIKE ?)";
        $term = '%' . $search_utente . '%';
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }

    if($search_libro != '') {
        $query .= " AND (Opera.Titolo LIKE ? OR Edizione.ISBN LIKE ?)";
        $term = '%' . $search_libro . '%';
        $params[] = $term;
        $params[] = $term;
    }

    $query .= " ORDER BY Data_Restituzione DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    require 'include/header.php';
?>

<h1>Storico Prestiti Conclusi</h1>
<a href="dashboard.php">Torna alla Dashboard</a> | <a href="prestiti.php">Vedi Prestiti Attivi</a>

<hr>

<form method="get">
    <label for="s_utente">Cerca Utente:</label>
    <input type="text" name="s_utente" value="<?= htmlspecialchars($search_utente)?>" placeholder="Nome o Tessera"> <br>

    <label for="s_libro">Cerca Libro:</label>
    <input type="text" name="s_libro" value="<?= htmlspecialchars($search_libro)?>" placeholder="Libro o ISBN"> <br>

    <button type="submit">Filtra Storico</button>
    <a href="storico.php"><button type="button">Reset</button></a>
</form>

<hr>

<?php if($stmt->rowCount() > 0): ?>
    <div class="table-wrapper">
        <table>
                <thead>
                    <tr>
                        <th>Utente</th>
                        <th>Libro</th>
                        <th>Periodo prestito</th>
                        <th>Data restituzione</th>
                        <th>Bibliotecario</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $stmt->fetch()):?>
                        <?php
                            $data_scadenza = new DateTime($row['Data_Fine']);
                            $data_rientro = new DateTime($row['Data_Restituzione']);
                            $is_ritardo = $data_rientro > $data_scadenza;
                        ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($row['Nome_Utente'] . ' ' . $row['Cognome_Utente']) ?> <br>
                                <small>(<?=htmlspecialchars($row['Tessera'])?>)</small>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['Titolo'])?> <br>
                                <small>(<?=htmlspecialchars($row['ISBN'])?>)</small>
                            </td>
                            <td>
                                Dal: <?= htmlspecialchars($row['Data_Inizio'])?> <br>
                                Al: <?= htmlspecialchars($row['Data_Fine'])?>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['Data_Restituzione'])?>
                                <?php if($is_ritardo):?>
                                    <br><small>IN RITARDO</small>
                                <?php endif;?>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['Nome_Biblio'] . ' ' . $row['Cognome_Biblio']) ?>
                            </td>
                        </tr>
                    <?php endwhile;?>
                </tbody>
            </table>
    </div>
<?php else:?>
    <p>Nessun prestito trovato nello storico con questi criteri</p>
<?php endif;?>

<?php
    require 'include/footer.php';
?>
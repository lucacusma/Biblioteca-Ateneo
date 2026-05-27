<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }

    require 'include/db.php';

    $search = $_GET['search'] ?? '';

    $filtro_categoria = $_GET['id_cat'] ?? '';

    $params = [];

    $query = "SELECT Edizione.ISBN, Edizione.Anno_Edizione, Edizione.Lingua_Pubblicazione, Edizione.Pagine,
                Opera.Titolo,
                Editore.Nome AS Nome_Editore,
                GROUP_CONCAT(DISTINCT CONCAT(Autore.Nome, ' ', Autore.Cognome) SEPARATOR ', ') AS Autori,
                (SELECT COUNT(*) FROM Copia WHERE Copia.ISBN = Edizione.ISBN AND Copia.Stato != 'Dismesso') AS Copie_Totali,
                (SELECT COUNT(*) FROM Copia WHERE Copia.ISBN = Edizione.ISBN AND Copia.Stato = 'Disponibile') AS Copie_Disponibili
                FROM Edizione
                JOIN Opera ON Edizione.Opera = Opera.ID_Opera
                JOIN Editore ON Edizione.Editore = Editore.ID_Editore
                JOIN Scrittura ON Opera.ID_Opera = Scrittura.Opera
                JOIN Autore ON Scrittura.Autore = Autore.ID_Autore";
                // JOIN Copia ON Edizione.ISBN = Copia.ISBN

    $where_clauses = [];

    if($search) {
        $where_clauses[] ="(Opera.Titolo LIKE ?
                            OR Autore.Nome LIKE ?
                            OR Autore.Cognome LIKE ?
                            OR Edizione.ISBN LIKE ?)";
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    if($filtro_categoria){
        $where_clauses[] = "EXISTS(
                                SELECT 1 FROM Classificazione cl
                                JOIN Categoria C ON cl.Categoria = C.ID_Categoria
                                WHERE cl.Opera = Opera.ID_Opera
                                AND (C.ID_Categoria = ? OR C.Macro_Categoria = ?)
                            )";
        $params[] = $filtro_categoria;
        $params[] = $filtro_categoria;
    }

    if(count($where_clauses) > 0){
        $query .= ' WHERE ' . implode(' AND ', $where_clauses);
    }

    $query .= " GROUP BY Edizione.ISBN ORDER BY (Copie_Totali = 0) ASC, (Copie_Disponibili = 0) ASC, Opera.Titolo ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    $stmt_cat = $pdo->query("SELECT ID_Categoria, Nome FROM Categoria ORDER BY Nome ASC");
    $categorie_filtro = $stmt_cat->fetchAll();

    require 'include/header.php';
?>

<h1>Catalogo Libri</h1>

<a href="dashboard.php">Torna alla Dashboard</a> | <a href="nuovo_libro.php">Aggiungi un nuovo libro</a>

<hr>

<form method="get">
    <label for="search">Cerca nel catalogo: </label>
    <input type="text" name="search" id="search" value="<?=htmlspecialchars($search)?>" placeholder="Titolo, Autore o ISBN...">

    <label for="id_cat" style="margin-left: 15px;">Categoria:</label>
    <select name="id_cat" id="id_cat">
        <option value="">-- Tutte --</option>
        <?php foreach($categorie_filtro as $c): ?>
            <option value="<?= $c['ID_Categoria'] ?>" <?= ($filtro_categoria == $c['ID_Categoria']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['Nome']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Cerca</button>
    <?php if($search): ?>
        <a href="libri.php"><button type="button">Mostra Tutti</button></a>
    <?php endif; ?>
</form>

<hr>

<h3>Elenco Edizioni</h3>

<?php if ($stmt->rowCount() > 0):?>
    <div class="table-wrapper">
        <table>
                <thead>
                    <tr>
                        <th>ISBN</th>
                        <th>Titolo</th>
                        <th>Autori</th>
                        <th>Editore</th>
                        <th>Lingua Edizione</th>
                        <th>Pagine</th>
                        <th>Copie (Disp./Tot.)</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $stmt->fetch()):?>
                        <tr>
                            <td><?=htmlspecialchars($row['ISBN'] ?? '')?></td>
                            <td><?=htmlspecialchars($row['Titolo'] ?? '')?></td>
                            <td><?=htmlspecialchars($row['Autori'] ?? 'Autore Sconosciuto')?></td>
                            <td>
                                <?=htmlspecialchars($row['Nome_Editore'] ?? '')?>
                                <small>(<?=htmlspecialchars($row['Anno_Edizione'] ?? 'N/D')?>)</small>
                            </td>
                            <td><?=htmlspecialchars($row['Lingua_Pubblicazione'] ?? '-')?></td>
                            <td><?=htmlspecialchars($row['Pagine'] ?? '-')?></td>
                            <td>
                                <strong><?=htmlspecialchars($row['Copie_Disponibili'] ?? '0')?></strong>
                                /
                                <?=htmlspecialchars($row['Copie_Totali'] ?? '0')?>
                            </td>
                            <td>
                                <a href="dettaglio_libro.php?isbn=<?=htmlspecialchars($row['ISBN'])?>">Scheda Libro</a>
                                -
                                <a href="nuova_copia.php?isbn=<?=htmlspecialchars($row['ISBN'])?>">Aggiungi Copia</a>
                            </td>
                        </tr>
                    <?php endwhile;?>
                </tbody>
            </table>
    </div>
<?php endif;?>

<?php
    require 'include/footer.php';
?>
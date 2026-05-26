<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require 'include/db.php';

    $search = $_GET['search'] ?? '';
    $filtro_categoria = $_GET['id_cat'] ?? '';

    $params = [];
    $where_clauses = [];

    $query = "SELECT Edizione.ISBN, Edizione.Anno_Edizione, Opera.Titolo, Editore.Nome AS Editore,
            GROUP_CONCAT(DISTINCT CONCAT(Autore.Nome, ' ', Autore.Cognome) SEPARATOR ', ') AS Autori,
            GROUP_CONCAT(DISTINCT Categoria.Nome SEPARATOR ', ') AS Categorie_String,
                (SELECT GROUP_CONCAT(Sede.Nome SEPARATOR ', ')
                FROM Copia
                JOIN Sede ON Copia.Sede = Sede.ID_Sede 
                WHERE Copia.ISBN = Edizione.ISBN AND Copia.Stato = 'Disponibile') AS Sedi_Disponibili
            FROM Edizione
            JOIN Opera ON Edizione.Opera = Opera.ID_Opera
            JOIN Editore ON Edizione.Editore = Editore.ID_Editore
            JOIN Scrittura ON Opera.ID_Opera = Scrittura.Opera
            JOIN Autore ON Scrittura.Autore = Autore.ID_Autore
            LEFT JOIN Classificazione ON Opera.ID_Opera = Classificazione.Opera
            LEFT JOIN Categoria ON Classificazione.Categoria = Categoria.ID_Categoria";

    if($search) { 
        $where_clauses[] = "(Opera.Titolo LIKE ?
                            OR Autore.Nome LIKE ?
                            OR Autore.Cognome LIKE ?
                            OR Edizione.ISBN LIKE ?)";
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }

    if($filtro_categoria) { 
        $where_clauses[] = "EXISTS(
                                SELECT 1 FROM Classificazione cl
                                JOIN Categoria C ON cl.Categoria = C.ID_Categoria
                                WHERE cl.Opera = Opera.ID_Opera
                                AND (C.ID_Categoria = ? OR Macro_Categoria = ?)
                            )";
        $params[] = $filtro_categoria;
        $params[] = $filtro_categoria;
    }

    if(count($where_clauses) > 0) {
        $query .= ' WHERE ' . implode(' AND ', $where_clauses);
    }

    $query .= " GROUP BY Edizione.ISBN ORDER BY (Sedi_Disponibili IS NULL) ASC, Opera.Titolo ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    $stmt_cat = $pdo->query("SELECT ID_Categoria, Nome FROM Categoria ORDER BY Nome ASC");
    $lista_categorie = $stmt_cat->fetchAll();

    require 'include/header.php';
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<div>
    <h1>Catalogo Biblioteca</h1>

    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
        <p>Benvenuto, Bibliotecario. <a href="dashboard.php">Vai alla Dashboard</a></p>
    <?php endif; ?>
</div>

<hr>

<form method="get">
    <label for="search">Cerca (Titolo, Autore, ISBN):</label><br>
    <input type="text" name="search" id="search" value="<?=htmlspecialchars($search)?>" placeholder="Inserisci testo...">
    <br><br>

    <label for="id_cat">Filtra per categoria:</label><br>
    <select name="id_cat" id="select_cat">
        <option value="">-- Tutte le categorie --</option>
        <?php foreach($lista_categorie as $cat):?>
            <option value="<?=htmlspecialchars($cat['ID_Categoria'])?>" <?= ($filtro_categoria == $cat['ID_Categoria']) ? 'selected' : '' ?>>
                <?=htmlspecialchars($cat['Nome'])?>
            </option>
        <?endforeach;?>
    </select>

    <br><br>

    <button type="submit">Cerca nel catalogo</button>

    <?php if($search || $filtro_categoria):?>
        <a href="index.php">Reimposta filtri</a>
    <?php endif;?>
</form>

<hr>

<h3>Catalogo</h3>

<?php if($stmt->rowCount() > 0):?>
    <div class="table-wrapper">
        <table>
                <thead>
                    <tr>
                        <th>Opera</th>
                        <th>Autori</th>
                        <th>Categoria</th>
                        <th>Sedi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $stmt->fetch()):?>
                        <tr>
                            <td>
                                <strong><?=htmlspecialchars($row['Titolo'])?></strong>
                                <br>
                                <?=htmlspecialchars($row['Editore'])?> (<?=htmlspecialchars($row['Anno_Edizione'])?>)
                                <br>
                                ISBN: <?=htmlspecialchars($row['ISBN'])?>
                            </td>
                            <td>
                                <?php
                                    $autori_safe = htmlspecialchars($row['Autori']);
                                    echo str_replace(', ', '<br>', $autori_safe); 
                                ?>
                            </td>
                            <td>
                                <?php
                                    $cat_safe = htmlspecialchars($row['Categorie_String']);
                                    echo str_replace(', ', '<br>', $cat_safe);
                                ?>
                            </td>
                            <td>
                                <?php if(!empty($row['Sedi_Disponibili'])):?>
                                    <?php
                                        $sedi_safe = htmlspecialchars($row['Sedi_Disponibili']);
                                        echo str_replace(', ', '<br>', $sedi_safe); 
                                    ?>
                                <?php else:?>
                                    <span style="color: #dc3545;">Non disponibile</span>
                                <?php endif;?>
                            </td>
                        </tr>
                    <?php endwhile;?>
                </tbody>
            </table>
    </div>
<?php else:?>
    <p>Nessun risultato trovato.</p>
<?php endif;?>

<script src="assets/js/index.js"></script>

<?php
    require 'include/footer.php';
?>
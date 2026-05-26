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

    //Restituzione Prestiti
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['azione']) && $_POST['azione'] === 'restituisci') {
        $id_prestito = $_POST['id_prestito'];
        $id_copia = $_POST['id_copia'];

        try{
            $stmt_isbn = $pdo->prepare("SELECT ISBN FROM Copia WHERE ID_Copia = :id");
            $stmt_isbn->execute(['id' => $id_copia]);
            $isbn_restituito = $stmt_isbn->fetchColumn();

            $query_prestito = "UPDATE Prestito
                                SET Data_Restituzione = NOW()
                                WHERE ID_Prestito = :id_prestito";
            
            $stmt_prestito = $pdo->prepare($query_prestito);

            $stmt_prestito->execute([
                'id_prestito' => $id_prestito
            ]);

            $query_copia = "UPDATE Copia
                            SET Stato = 'Disponibile'
                            WHERE ID_Copia = :id_copia";
            
            $stmt_copia = $pdo->prepare($query_copia);

            $stmt_copia->execute([
                'id_copia' => $id_copia
            ]);

            $query_check = "SELECT U.Nome, U.Cognome
                            FROM Prenotazione P
                            JOIN Utente U ON P.Utente = U.ID_Utente
                            WHERE P.ISBN = :isbn
                            AND P.Stato = 'Attiva'
                            ORDER BY P.Data ASC
                            LIMIT 1";
            $stmt_check = $pdo->prepare($query_check);
            $stmt_check->execute(['isbn' => $isbn_restituito]);
            $prenotazione = $stmt_check->fetch();

            if ($prenotazione) {
                $_SESSION['messaggio'] = "Libro restituito. ATTENZIONE: Questo libro è prenotato da " . 
                                        htmlspecialchars($prenotazione['Nome'] . " " . $prenotazione['Cognome']);
            }
            else {
                $_SESSION['messaggio'] = 'Prestito restituito con successo!';
            }

            header("Location: prestiti.php");
            exit;
        }
        catch (PDOException $e){
            $messaggio = 'Errore: ' . $e->getMessage();
        }
    }

    //Visualizzazione prestiti attivi
    $query = "SELECT Prestito.ID_Prestito, Prestito.Data_Inizio, Prestito.Data_Fine,
                     Utente.Nome AS Nome_Utente, Utente.Cognome AS Cognome_Utente, Utente.Codice_Tessera AS Tessera,
                     Opera.Titolo, Copia.ID_Copia, Edizione.ISBN, Sede.Nome AS Nome_Sede, Bibliotecario.Nome, Bibliotecario.Cognome
                FROM Prestito
                JOIN Utente ON Prestito.Utente = Utente.ID_Utente
                JOIN Copia ON Prestito.Copia = Copia.ID_Copia
                JOIN Edizione ON Copia.ISBN = Edizione.ISBN
                JOIN Opera ON Edizione.Opera = Opera.ID_Opera
                JOIN Bibliotecario ON Prestito.Bibliotecario = Bibliotecario.ID_Bibliotecario
                JOIN Sede ON Copia.Sede = Sede.ID_Sede
                WHERE Prestito.Data_Restituzione IS NULL
                ORDER BY Data_Fine ASC";

    $stmt = $pdo->query($query);

    require 'include/header.php';
?>

<h1>Prestiti in Corso</h1>
<a href="dashboard.php">Torna alla Dashboard</a> | <a href="storico.php">Vedi Storico Prestiti</a>

<hr>

<?php if ($messaggio): ?>
    <p><strong><?= htmlspecialchars($messaggio) ?></strong></p>
    <hr>
<?php endif; ?>

<?php if($stmt->rowCount() > 0): ?>
    <div class="table-wrapper">
        <table>
                <thead>
                    <tr>
                        <th>Utente</th>
                        <th>Libro</th>
                        <th>Data Inizio</th>
                        <th>Scadenza</th>
                        <th>Stato</th>
                        <th>Azione</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $stmt->fetch()): ?>
                        <?php 
                            $scadenza = new DateTime($row['Data_Fine']);
                            $oggi = new DateTime();
                            $in_ritardo = $oggi > $scadenza;
                        ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($row['Cognome_Utente'] . ' ' . $row['Nome_Utente']); ?> <br>
                                <small>(<?= htmlspecialchars($row['Tessera']); ?>)</small>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['Titolo']); ?> <br>
                                <small>(<?=htmlspecialchars($row['ISBN'])?>)</small>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['Data_Inizio'])?>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['Data_Fine'])?>
                            </td>
                            <td>
                                <?php if ($in_ritardo):?>
                                    SCADUTO
                                <?php else: ?>
                                    In corso
                                <?php endif; ?> 
                            </td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Confermi la restituzione del libro?');">
                                    <input type="hidden" name="azione" value="restituisci">
                                    <input type="hidden" name="id_prestito" value="<?= $row['ID_Prestito'] ?>">
                                    <input type="hidden" name="id_copia" value="<?= $row['ID_Copia'] ?>">
                                    <button type="submit">Restituisci</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
    </div>
<?php endif; ?>

<?php
    require 'include/footer.php';
?>
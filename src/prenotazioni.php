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

    //Gestione Azioni
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['azione'])) {
        $id_prenotazione = $_POST['id_prenotazione'];

        try {
            if ($_POST['azione'] === 'annulla'){
                $query_update = "UPDATE Prenotazione
                                SET Stato = 'Cancellata'
                                WHERE ID_Prenotazione = :id";
                $stmt_update = $pdo->prepare($query_update);
                $stmt_update->execute(['id' => $id_prenotazione]);

                $_SESSION['messaggio_flash'] = 'Prenotazione cancellata con successo';
                header("Location: prenotazioni.php");
                exit;
            }
            elseif ($_POST['azione'] === 'soddisfa'){
                $query_info = "SELECT Utente, ISBN
                                FROM Prenotazione
                                WHERE ID_Prenotazione = :id";
                $stmt_info = $pdo->prepare($query_info);
                $stmt_info->execute(['id' => $id_prenotazione]);
                $prenotazione = $stmt_info->fetch();

                if(!$prenotazione) {
                    throw new Exception('Prenotazione non trovata');
                }

                $id_utente = $prenotazione['Utente'];
                $isbn = $prenotazione['ISBN'];

                $query_copia = "SELECT ID_Copia
                                FROM Copia
                                WHERE ISBN = :isbn AND Stato = 'Disponibile'
                                LIMIT 1";
                $stmt_copia = $pdo->prepare($query_copia);
                $stmt_copia->execute(['isbn' => $isbn]);
                $copia = $stmt_copia->fetch();

                if(!$copia) {
                    throw new Exception('Nessuna copia fisica disponibile al momento');
                }

                $id_copia = $copia['ID_Copia'];
                $id_bibliotecario = $_SESSION['id_bibliotecario'];
                $data_inizio = date('Y-m-d');
                $data_fine = date('Y-m-d', strtotime('+30 days'));

                try {
                    $pdo->beginTransaction();

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

                    $query_update_copia = "UPDATE Copia
                                            SET Stato = 'In prestito'
                                            WHERE ID_Copia = :id";
                    $stmt_update_copia = $pdo->prepare($query_update_copia);
                    $stmt_update_copia->execute(['id' => $id_copia]);

                    $query_update_prenotazione = "UPDATE Prenotazione
                                                    SET Stato = 'Soddisfatta'
                                                    WHERE ID_Prenotazione = :id";
                    $stmt_update_prenotazione = $pdo->prepare($query_update_prenotazione);
                    $stmt_update_prenotazione->execute(['id' => $id_prenotazione]);

                    $pdo->commit();

                    $_SESSION['messaggio_flash'] = "Richiesta soddisfatta! È stato registrato automaticamente un nuovo prestito.";
                    header("Location: prenotazioni.php");
                    exit;

                } catch (Exception $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    throw $e;
                }
            }
        }
        catch(Exception $e) {
            $messaggio = 'Errore: ' . $e->getMessage();
        }

        
    }

    //Prenotazioni attive
    $query = "SELECT Prenotazione.ID_Prenotazione, Prenotazione.Data, Prenotazione.Stato,
                    Utente.Nome AS Nome_Utente, Utente.Cognome AS Cognome_Utente, Utente.Codice_Tessera AS Tessera,
                    Opera.Titolo, Edizione.ISBN
                FROM Prenotazione
                JOIN Utente ON Prenotazione.Utente = Utente.ID_Utente
                JOIN Edizione ON Prenotazione.ISBN = Edizione.ISBN
                JOIN Opera ON Edizione.Opera = Opera.ID_Opera
                WHERE Prenotazione.Stato = 'Attiva'
                ORDER BY Prenotazione.Data ASC";

    $stmt = $pdo->query($query);

    require 'include/header.php';
?>

<h1>Gestione Prenotazioni</h1>
<a href="dashboard.php">Torna alla Dashboard</a> | <a href="nuova_prenotazione.php">Nuova Prenotazione</a>

<hr>

<?php if ($messaggio): ?>
    <p><strong><?= htmlspecialchars($messaggio) ?></strong></p>
<?php endif; ?>

<?php if($stmt->rowCount() > 0): ?>
    <div class="table-wrapper">
        <table>
                <thead>
                    <tr>
                        <th>Data Richiesta</th>
                        <th>Utente</th>
                        <th>Libro Richiesto</th>
                        <th>Azione</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $stmt->fetch()):?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($row['Data'])?>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['Nome_Utente'] . ' ' . $row['Cognome_Utente'])?> <br>
                                <small>(<?=htmlspecialchars($row['Tessera'])?>)</small>
                            </td>
                            <td>
                                <?= htmlspecialchars($row['Titolo']) ?> <br>
                                <small>(<?= htmlspecialchars($row['ISBN'])?>)</small>
                            </td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Confermi la cancellazione della prenotazione?');">
                                    <input type="hidden" name="azione" value="annulla">
                                    <input type="hidden" name="id_prenotazione" value="<?= $row['ID_Prenotazione'] ?>">
                                    <button type="submit">Annulla</button>
                                </form>
                                
                                <form method="POST" onsubmit="return confirm('Confermi che la richiesta è stata soddisfatta?');">
                                    <input type="hidden" name="azione" value="soddisfa">
                                    <input type="hidden" name="id_prenotazione" value="<?= $row['ID_Prenotazione'] ?>">
                                    <button type="submit">Soddisfa</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile;?>
                </tbody>
            </table>
    </div>
<?php else: ?>
    <p>Nessuna prenotazione attiva</p>
<?php endif;?>

<?php
    require 'include/footer.php';
?>
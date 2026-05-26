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

    $id_utente = $_GET['id'] ?? null; 

    if(!$id_utente) {
        header('Location: utenti.php');
        exit;
    }

    //azioni
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if(isset($_POST['azione']) && $_POST['azione'] === 'cambia_stato') {
            $nuovo_stato = $_POST['nuovo_stato'];

            try {
                $stmt_stato = $pdo->prepare("UPDATE Utente SET Stato = :stato WHERE ID_Utente = :id");
                $stmt_stato->execute([
                    'stato' => $nuovo_stato,
                    'id' => $id_utente
                ]);

                $_SESSION['messaggio'] = "Stato aggiornato a <strong>" . $nuovo_stato . '</strong>';
            }
            catch(Exception $e) {
                $_SESSION['messaggio'] = 'Errore aggiornamento stato: ' . $e->getMessage();
            }
        }

        if(isset($_POST['azione']) && $_POST['azione'] === 'restituisci') {
            $id_prestito = $_POST['id_prestito'];
            $id_copia = $_POST['id_copia'];

            try{
                $pdo->beginTransaction();

                $stmt_prestito = $pdo->prepare("UPDATE Prestito SET Data_Restituzione = CURRENT_DATE WHERE ID_Prestito = :id");
                $stmt_prestito->execute(['id' => $id_prestito]);

                $stmt_copia = $pdo->prepare("UPDATE Copia SET Stato = 'Disponibile' WHERE ID_Copia = :id");
                $stmt_copia->execute(['id' => $id_copia]);

                $pdo->commit();
                $_SESSION['messaggio'] = "Libro restituito con successo";
            }
            catch (Exception $e) {
                if($pdo->inTransaction()){
                    $pdo->rollback();
                }
                $_SESSION['messaggio'] = "Errore nella restituzione: " . $e->getMessage();
            }
        }

        header('Location: dettaglio_utente.php?id=' . $id_utente);
        exit;
    }

    //recupero dati
    $query_utente = "SELECT Utente.*, Dipartimento.Nome AS Nome_Dipartimento
                    FROM Utente
                    JOIN Dipartimento ON Utente.Dipartimento = Dipartimento.ID_Dipartimento
                    WHERE Utente.ID_Utente = :id";
    $stmt_utente = $pdo->prepare($query_utente);
    $stmt_utente->execute(['id' => $id_utente]);
    $utente = $stmt_utente->fetch();

    if(!$utente) {
        echo 'Utente non trovato';
        exit;
    }

    $query_attivi = "SELECT Prestito.ID_Prestito, Prestito.Data_Inizio, Prestito.Data_Fine,
                            Opera.Titolo, Edizione.ISBN, Copia.ID_Copia
                    FROM Prestito
                    JOIN Copia ON Prestito.Copia = Copia.ID_Copia
                    JOIN Edizione ON Copia.ISBN = Edizione.ISBN
                    JOIN Opera ON Edizione.Opera = Opera.ID_Opera
                    WHERE Prestito.Utente = :id AND Prestito.Data_Restituzione IS NULL
                    ORDER BY Prestito.Data_Fine ASC";
    $stmt_attivi = $pdo->prepare($query_attivi);
    $stmt_attivi->execute(['id' => $id_utente]);

    $query_pren = "SELECT Prenotazione.ID_Prenotazione, Prenotazione.Data, Opera.Titolo
                    FROM Prenotazione
                    JOIN Edizione ON Prenotazione.ISBN = Edizione.ISBN
                    JOIN Opera ON Edizione.Opera = Opera.ID_Opera
                    WHERE Prenotazione.Utente = :id AND Prenotazione.Stato = 'Attiva'
                    ORDER BY Prenotazione.Data ASC";
    $stmt_pren = $pdo->prepare($query_pren);
    $stmt_pren->execute(['id' => $id_utente]);

    $query_storico = "SELECT P.Data_Inizio, P.Data_Fine, P.Data_Restituzione, O.Titolo
                    FROM Prestito P
                    JOIN Copia C ON P.Copia = C.ID_Copia
                    JOIN Edizione E ON C.ISBN = E.ISBN
                    JOIN Opera O ON E.Opera = O.ID_Opera
                    WHERE P.Utente = :id AND P.Data_Restituzione IS NOT NULL
                    ORDER BY P.Data_Restituzione DESC";
    $stmt_storico = $pdo->prepare($query_storico);
    $stmt_storico->execute(['id' => $id_utente]);

    require 'include/header.php';
?>

<h1>Scheda Utente</h1>
<a href="dashboard.php">Torna alla Dashboard</a> | <a href="utenti.php">Elenco Utenti</a> | <a href="nuovo_prestito.php?q_utente=<?= urlencode($utente['Codice_Tessera']) ?>">Nuovo Prestito per questo utente</a>

<hr>

<?php if ($messaggio): ?>
    <p><strong><?= $messaggio ?></strong></p>
<?php endif; ?>

<h2>
    <?=htmlspecialchars($utente['Nome'] . ' ' . $utente['Cognome'] . 
    ($utente['Tipo_Utente'] === 'Studente' ? 
    ' (' . $utente['Matricola'] . ')' : 
    ''))?>
</h2>
<p><strong>Tessera: </strong><?=htmlspecialchars($utente['Codice_Tessera'])?></p>
<p><strong>Email: </strong><?=htmlspecialchars($utente['Email'])?></p>
<p><strong>Telefono: </strong><?=htmlspecialchars($utente['Telefono'] ?? '-')?></p>
<p><strong>Dipartimento: </strong><?=htmlspecialchars($utente['Nome_Dipartimento'])?></p>
<p><strong>Tipo: </strong><?=htmlspecialchars($utente['Tipo_Utente'])?></p>

<?php if($utente['Tipo_Utente'] === 'Studente'):?>
    <p><strong>Corso di Laurea: </strong><?=htmlspecialchars($utente['Corso_Laurea'])?></p>
<?php else:?>
    <p><strong>Ufficio: </strong><?=htmlspecialchars($utente['Ufficio'])?></p>
<?php endif;?>

<h3>
    Stato attuale:
    <span><?=strtoupper($utente['Stato'])?></span>
</h3>

<form method="post" onsubmit="return confirm('Sei sicuro di voler cambiare lo stato di questo utente?');">
    <input type="hidden" name="azione" value="cambia_stato">
    <?php if($utente['Stato'] === 'Attivo'):?>
        <input type="hidden" name="nuovo_stato" value="Sospeso">
        <button type="submit">SOSPENDI UTENTE</button>
        <br>
        <small>L'utente non potrà prendere nuovi libri</small>
    <?php else:?>
        <input type="hidden" name="nuovo_stato" value="Attivo">
        <button type="submit">RIATTIVA UTENTE</button>
    <?php endif;?>
</form>

<hr>

<h3>Libri attualmente in prestito</h3>
<?php if($stmt_attivi->rowCount() > 0):?>
    <div class="table-wrapper">
        <table>
                <thead>
                    <tr>
                        <th>Titolo</th>
                        <th>Data Inizio</th>
                        <th>Scadenza</th>
                        <th>Stato</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $stmt_attivi->fetch()):
                        $scadenza = new DateTime($row['Data_Fine']);
                        $oggi = new DateTime();
                        $scaduto = $oggi > $scadenza;    
                    ?>
                        <tr>
                            <td>
                                <?=htmlspecialchars($row['Titolo'])?><br>
                                <small>#<?=htmlspecialchars($row['ID_Copia'])?></small>
                            </td>
                            <td><?=$row['Data_Inizio']?></td>
                            <td><?=$row['Data_Fine']?></td>
                            <td><?=$scaduto ? 'SCADUTO' : 'In corso'?></td>
                            <td>
                                <form method="post" onsubmit="return confirm('Confermi la restituzione?');">
                                    <input type="hidden" name="azione" value="restituisci">
                                    <input type="hidden" name="id_prestito" value="<?=$row['ID_Prestito']?>">
                                    <input type="hidden" name="id_copia" value="<?=$row['ID_Copia']?>">
                                    <button type="submit">Restituisci</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile;?>
                </tbody>
            </table>
    </div>
<?php else:?>
    <p>Nessun prestito in corso</p>
<?php endif;?>

<hr>

<h3>Prenotazioni Attive</h3>

<?php if($stmt_pren->rowCount() > 0):?>
    <ul>
        <?php while($pren = $stmt_pren->fetch()): ?>
            <li>
                <strong><?=htmlspecialchars($pren['Titolo'])?></strong> - Richiesto il: <?=htmlspecialchars($pren['Data'])?>
            </li>
        <?php endwhile;?>
    </ul>
<?php else:?>
    <p>Nessuna prenotazione Attiva</p>
<?php endif;?>

<hr>

<h3>Storico prestiti</h3>

<?php if($stmt_storico->rowCount() > 0):?>
    <div class="table-wrapper">
        <table>
                <thead>
                    <tr>
                        <th>Titolo</th>
                        <th>Data Prestito</th>
                        <th>Data Restituzione</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($st = $stmt_storico->fetch()):?>
                        <tr>
                            <td><?=htmlspecialchars($st['Titolo'])?></td>
                            <td><?=htmlspecialchars($st['Data_Inizio'])?></td>
                            <td><?=htmlspecialchars($st['Data_Restituzione'])?></td>
                        </tr>
                    <?php endwhile;?>
                </tbody>
            </table>
    </div>
<?php else:?>
    <p>Nessun prestito nello storico</p>
<?php endif;?>

<?php
    require 'include/footer.php';
?>
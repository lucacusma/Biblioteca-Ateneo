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
    //azioni

    $password_provvisoria = 'Cambiami123!';

    if ($_SERVER["REQUEST_METHOD"] == "POST"){
        //nuovo bibliotecario
        if(isset($_POST['azione']) && $_POST['azione'] === 'nuovo') {
            $nome = $_POST['nome'];
            $cognome = $_POST['cognome'];
            $username = $_POST['username'];

            try {
                $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM Bibliotecario WHERE Username = :user");
                $stmt_check->execute(['user' => $username]);

                if($stmt_check->fetchColumn() > 0){
                    throw new Exception("Username " . $username . " già in uso");
                }

                $hash = password_hash($password_provvisoria, PASSWORD_DEFAULT);

                $stmt_ins = $pdo->prepare("INSERT INTO Bibliotecario(Nome, Cognome, Username, Password, Stato)
                                            VALUES (:nome, :cognome, :username, :hash, 'Attivo')");
                $stmt_ins->execute([
                    'nome' => $nome,
                    'cognome' => $cognome,
                    'username' => $username,
                    'hash' => $hash
                ]);

                $_SESSION['messaggio'] = 'Bibliotecario creato, password (cambiarla al primo accesso): <strong>' . $password_provvisoria . '</strong>';
            }
            catch(Exception $e) {
                $_SESSION['messaggio'] = 'Errore: ' . $e->getMessage();
            }
        }

        //eliminazione
        if(isset($_POST['azione']) && $_POST['azione'] === 'cambia_stato') {
            $id_target = $_POST['id_biblio'];
            $nuovo_stato = $_POST['nuovo_stato'];

            if($id_target == $_SESSION['id_bibliotecario'] && $nuovo_stato == 'Disattivato') {
                $_SESSION['messaggio'] = 'Non puoi eliminare il tuo account';
            }
            else {
                try {
                    $stmt_update = $pdo->prepare("UPDATE Bibliotecario SET Stato = :stato WHERE ID_Bibliotecario = :id");
                    $stmt_update->execute([
                        'stato' => $nuovo_stato,
                        'id' => $id_target
                    ]);

                    $azione_testo = ($nuovo_stato == 'Attivo') ? "Riattivato" : "Disattivato";
                    $_SESSION['messaggio'] = 'Account ' . $azione_testo . " con successo";
                }
                catch(Exception $e) {
                    $_SESSION['messaggio'] = 'Errore: ' . $e->getMessage();
                }
            }
        }

        header('Location: gestione_bibliotecari.php');
        exit;
    }

    //elenco bibliotecari
    $stmt_list = $pdo->query("SELECT * FROM Bibliotecario ORDER BY (ID_Bibliotecario =". $_SESSION['id_bibliotecario'].") DESC, Stato ASC, ID_Bibliotecario ASC");

    require 'include/header.php';

?>

<h1>Gestione Staff</h1>
<a href="dashboard.php">Torna alla dashboard</a>

<hr>

<?php if ($messaggio): ?>
    <p><strong><?= $messaggio ?></strong></p>
<?php endif; ?>

<h3>Aggiungi un Bibliotecario</h3>
<form method="post">
    <input type="hidden" name="azione" value="nuovo">
    <input type="text" name="nome" placeholder="Nome" required> <br>
    <input type="text" name="cognome" placeholder="Cognome" required> <br>
    <input type="text" name="username" placeholder="Username" required> <br>
    <button type="submit">Crea Account</button>
</form>
<small>Password provvisoria: <strong><?=htmlspecialchars($password_provvisoria)?></strong></small>

<hr>

<h3>Elenco Bibliotecari</h3>

<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome e Cognome</th>
                <th>Username</th>
                <th>Stato</th>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php while($b = $stmt_list->fetch()): ?>
                <tr>
                    <td><?=htmlspecialchars($b['ID_Bibliotecario'])?></td>
                    <td><?=htmlspecialchars($b['Nome'] . ' ' . $b['Cognome'])?></td>
                    <td><?=htmlspecialchars($b['Username'])?></td>
                    <td><?=htmlspecialchars($b['Stato'])?></td>
                    <td>
                        <?php if($b['ID_Bibliotecario'] != $_SESSION['id_bibliotecario']):?>
                            <form method="post">
                                <input type="hidden" name="azione" value="cambia_stato">
                                <input type="hidden" name="id_biblio" value="<?= $b['ID_Bibliotecario']?>">
    
                                <?php if ($b['Stato'] === 'Attivo'): ?>
                                    <input type="hidden" name="nuovo_stato" value="Disattivato">
                                    <button type="submit" onclick="return confirm('Vuoi disattivare questo account? L\'utente non potrà più accedere.');">
                                        Disattiva
                                    </button>
                                <?php else: ?>
                                    <input type="hidden" name="nuovo_stato" value="Attivo">
                                    <button type="submit">
                                        Riattiva
                                    </button>
                                <?php endif; ?>
                            </form>
                        <?php else:?>
                            <small><a href="profilo.php">(Tu)</a></small>
                        <?php endif;?>
                    </td>
                </tr>
            <?php endwhile;?>
        </tbody>
    </table>
</div>

<?php require 'include/footer.php' ?>
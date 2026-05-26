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

    //cambio password
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $old_pass = $_POST['old_pass'];
        $new_pass = $_POST['new_pass'];
        $confirm_pass = $_POST['confirm_pass'];

        $id_user = $_SESSION['id_bibliotecario'];

        if($new_pass !== $confirm_pass){
            $_SESSION['messaggio'] = "Errore: Le password non coincidono";
        }
        elseif(strlen($new_pass) < 8) {
            $_SESSION['messaggio'] = "Errore: La password deve avere almeno 8 caratteri";
        }
        else{
            try{
                $stmt = $pdo->prepare("SELECT Password FROM Bibliotecario WHERE ID_Bibliotecario = :id");
                $stmt->execute(['id' => $id_user]);
                $current_hash = $stmt->fetchColumn();

                if(password_verify($old_pass, $current_hash)) {
                    $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);

                    $stmt_upd = $pdo->prepare("UPDATE Bibliotecario SET Password = :pass WHERE ID_Bibliotecario = :id");
                    $stmt_upd->execute([
                        'pass' => $new_hash,
                        'id' => $id_user
                    ]);

                    $_SESSION['messaggio'] = 'Password aggiornata con successo!';
                }
                else {
                    $_SESSION['messaggio'] = 'Errore: la vecchia password inserita è errata';
                }
            } catch(Exception $e) {
                $_SESSION['messaggio'] = 'Errore: ' . $e->getMessage();
            }
        }

        header('Location: profilo.php');
        exit;
    }

    //recupero dati
    $stmt_info = $pdo->prepare("SELECT Nome, Cognome, Username, Stato FROM Bibliotecario WHERE ID_Bibliotecario = :id");
    $stmt_info->execute(['id' => $_SESSION['id_bibliotecario']]);
    $info_utente = $stmt_info->fetch();

    require 'include/header.php';
?>

<h1>Il mio profilo</h1>
<a href="dashboard.php">Torna alla dashboard</a>

<hr>

<h2>Dati personali</h2>
<p><strong>Nome: </strong><?=htmlspecialchars($info_utente['Nome'])?></p>
<p><strong>Cognome: </strong><?=htmlspecialchars($info_utente['Cognome'])?></p>
<p><strong>Username: </strong><?=htmlspecialchars($info_utente['Username'])?></p>
<p><strong>ID Staff: </strong>#<?=htmlspecialchars($_SESSION['id_bibliotecario'])?></p>
<p><strong>Stato: </strong><?=htmlspecialchars($info_utente['Stato'])?></p>

<h3>Cambia password</h3>

<?php if ($messaggio): ?>
    <p><strong><?= $messaggio ?></strong></p>
<?php endif; ?>

<form method="post">
    <label for="old_pass">Vecchia password:</label> <br>
    <input type="password" name="old_pass" id="old_pass" required> <br>

    <label for="new_pass">Nuova password:</label> <br>
    <input type="password" name="new_pass" id="new_pass" minlength="8" required> <br>

    <label for="confirm_pass">Conferma nuova password:</label> <br>
    <input type="password" name="confirm_pass" id="confirm_pass" minlength="8" required> <br>

    <button type="submit">Cambia Password</button>
</form>

<?php
    require 'include/footer.php';
?>
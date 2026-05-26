<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    require 'include/db.php';

    $errore = '';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $query = 'SELECT * FROM Bibliotecario WHERE Username = :user';
        $stmt = $pdo->prepare($query);

        $stmt->execute([
            'user' => $username
        ]);

        $utente = $stmt->fetch();

        if($utente && password_verify($password, $utente['Password'])){    #credenziali corrette
            if ($utente['Stato'] !== 'Attivo') {
                $errore = 'Account disattivato. Contatta l\'amministratore.';
            }
            else {
                $_SESSION['logged_in'] = true;
                $_SESSION['id_bibliotecario'] = $utente['ID_Bibliotecario'];
                $_SESSION['nome'] = $utente['Nome'] . ' ' . $utente['Cognome'];

                header('Location: dashboard.php');
                exit;
            }
        } 
        else { 
            $errore = 'Username o password errati';
        }
    }
    require 'include/header.php';

    if ($errore != '') {
        echo $errore;
    }
?>

<form method='POST'>
    <label for="username">Username</label>
    <input type="text" id='username' name='username'>

    <label for="password">Password</label>
    <input type="password" id='password' name='password'>

    <input type="submit" value='Invia'>
</form>

<?php
    require 'include/footer.php';
?>
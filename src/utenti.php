<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }

    require 'include/db.php';

    $messaggio ='';

    if (isset($_SESSION['messaggio'])) {
        $messaggio = $_SESSION['messaggio'];
        unset($_SESSION['messaggio']);
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $nome = $_POST['nome'];
        $cognome = $_POST['cognome'];
        $email = $_POST['email'];
        $id_dipartimento = $_POST['dipartimento'];
        $tipo_utente = $_POST['tipo_utente'];

        $telefono = !empty($_POST['telefono']) ? $_POST['telefono'] : NULL;
        $matricola = !empty($_POST['matricola']) ? $_POST['matricola'] : NULL;
        $ufficio = !empty($_POST['ufficio']) ? $_POST['ufficio'] : NULL;
        $corso_laurea = !empty($_POST['corso_laurea']) ? $_POST['corso_laurea'] : NULL;

        $codice_tessera = '';
        $tessera_esistente = true;

        do {
            $codice_tessera = 'TESS-' . strtoupper(bin2hex(random_bytes(3)));

            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM Utente WHERE Codice_Tessera = ?");
            $stmt_check->execute([$codice_tessera]);
            $count = $stmt_check->fetchColumn();

            if ($count == 0) {
                $tessera_esistente = false;
            }
        } while ($tessera_esistente);

            try{
                $query =    "INSERT INTO Utente (Nome, Cognome, Email, Codice_Tessera, Telefono, Tipo_Utente, Dipartimento, Matricola, Ufficio, Corso_Laurea)
                            VALUES (:nome, :cognome, :email, :tessera, :tel, :tipo, :dip, :matr, :uff, :corso)";

                $stmt = $pdo->prepare($query);
                $stmt->execute([
                    'nome' => $nome,
                    'cognome' => $cognome,
                    'email' => $email,
                    'tessera' => $codice_tessera,
                    'tel' => $telefono,
                    'tipo' => $tipo_utente,
                    'dip' => $id_dipartimento,
                    'matr' => $matricola,
                    'uff' => $ufficio,
                    'corso' => $corso_laurea
                ]);

                $_SESSION['messaggio'] = "Utente inserito con successo. Tessera assegnata: <strong>$codice_tessera</strong>";
                header("Location: utenti.php");
        }
        catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $messaggio = "Errore: Email o Codice Tessera già esistenti.";
            } else {
                $messaggio = "Errore Database: " . $e->getMessage();
            }
        }
    }

        $stmt_dip = $pdo->query("SELECT ID_Dipartimento, Nome FROM Dipartimento ORDER BY Nome ASC");
        $dipartimenti = $stmt_dip->fetchAll();

        $query_utenti = "SELECT Utente.*, Dipartimento.Nome AS Nome_Dipartimento
                        FROM Utente
                        JOIN Dipartimento ON Utente.Dipartimento = Dipartimento.ID_Dipartimento
                        ORDER BY Utente.Cognome ASC";
        $lista_utenti = $pdo->query($query_utenti);

    require 'include/header.php';
?>

<h1>Gestione Utenti</h1>
<a href="dashboard.php">Torna alla Dashboard</a>

<?php if ($messaggio): ?>
    <p><strong><?= htmlspecialchars($messaggio) ?></strong></p>
<?php endif; ?>

<hr>

<h2>Inserisci Nuovo Utente</h2>
<form method="POST" action="utenti.php">
    <label for="nome">Nome:</label><br>
    <input type="text" id="nome" name="nome" required><br>

    <label for="cognome">Cognome:</label><br>
    <input type="text" id="cognome" name="cognome" required><br>

    <label for="email">Email:</label><br>
    <input type="email" id="email" name="email" required><br>

    <label for="telefono">Telefono:</label><br>
    <input type="text" id="telefono" name="telefono"><br>

    <label for="tipo_utente">Tipo Utente:</label><br>
    <select id="tipo_utente" name="tipo_utente" onchange="mostraCampi()" required>
        <option value="" disabled selected>-- Seleziona --</option>
        <option value="Studente">Studente</option>
        <option value="Professore">Professore</option>
        <option value="Personale">Personale Amministrativo</option>
    </select><br>

    <label for="dipartimento">Dipartimento:</label><br>
    <select id="dipartimento" name="dipartimento" required>
        <?php foreach ($dipartimenti as $dip): ?>
            <option value="<?= $dip['ID_Dipartimento'] ?>">
                <?= htmlspecialchars($dip['Nome']) ?>
            </option>
        <?php endforeach; ?>
    </select><br>

    <div id="campi_studente" style="display:none;">
        <strong>Dati Studente:</strong><br>
        <label for="matricola">Matricola:</label><br>
        <input type="text" id="matricola" name="matricola"><br>

        <label for="corso_laurea">Corso di Laurea:</label><br>
        <input type="text" id="corso_laurea" name="corso_laurea"><br>
    </div>

    <div id="campi_staff" style="display:none;">
        <strong>Dati Staff:</strong><br>
        <label for="ufficio">Ufficio:</label><br>
        <input type="text" id="ufficio" name="ufficio"><br>
    </div>
    
    <br>
    <button type="submit">Registra Utente</button>
</form>

<hr>

<h2>Elenco Utenti Registrati</h2>
<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>Tessera</th>
                <th>Nome e Cognome</th>
                <th>Tipo</th>
                <th>Stato</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $lista_utenti->fetch()): ?>
            <tr>
                <td><?= htmlspecialchars($row['Codice_Tessera']) ?></td>
                <td>
                    <a href="dettaglio_utente.php?id=<?=$row['ID_Utente']?>">
                        <?= htmlspecialchars($row['Cognome'] . ' ' . $row['Nome']) ?>
                    </a>
                </td>
                <td><?= htmlspecialchars($row['Tipo_Utente']) ?></td>
                <td><?= htmlspecialchars($row['Stato']) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
    function mostraCampi() {
        var tipo = document.getElementById("tipo_utente").value;
        var divStudente = document.getElementById("campi_studente");
        var divStaff = document.getElementById("campi_staff");

        divStudente.style.display = "none";
        divStaff.style.display = "none";

        if (tipo === "Studente") {
            divStudente.style.display = "block";
        } else if (tipo === "Personale" || tipo === "Professore") {
            divStaff.style.display = "block";
        }
    }
</script>

<?php
    require 'include/footer.php';
?>
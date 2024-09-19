<?php
session_start();
include('../includes/db.php'); // Verbindung zur Datenbank herstellen

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['password']) && isset($_POST['confirm_password']) && isset($_GET['code'])) {
        $password = htmlspecialchars(trim($_POST['password']));
        $confirm_password = htmlspecialchars(trim($_POST['confirm_password']));
        $reset_code = htmlspecialchars(trim($_GET['code']));

        if ($password === $confirm_password) {
            // Prüfen, ob der Reset-Code gültig ist
            $sql = "SELECT email FROM password_reset WHERE reset_code = ? AND expiration > NOW()";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $reset_code);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                $stmt->bind_result($email);
                $stmt->fetch();

                // Passwort hashen
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                // Passwort in der Tabelle `users` aktualisieren
                $sql = "UPDATE users SET password = ? WHERE email = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ss', $hashed_password, $email);
                $stmt->execute();

                // Reset-Code löschen
                $sql = "DELETE FROM password_reset WHERE reset_code = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('s', $reset_code);
                $stmt->execute();

                echo "<div class='alert alert-success'>Ihr Passwort wurde erfolgreich zurückgesetzt.</div>";
            } else {
                echo "<div class='alert alert-danger'>Der Reset-Code ist ungültig oder abgelaufen.</div>";
            }

            $stmt->close();
        } else {
            echo "<div class='alert alert-danger'>Die Passwörter stimmen nicht überein.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passwort zurücksetzen</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .password-strength {
            height: 5px;
            width: 100%;
            background-color: #e0e0e0;
            margin-top: 5px;
        }
        .password-strength-bar {
            height: 100%;
            width: 0;
            background-color: red;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Passwort zurücksetzen</h2>
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <form method="post" action="">
                    <div class="form-group">
                        <label for="password">Neues Passwort</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="password-strength">
                            <div id="password-strength-bar" class="password-strength-bar"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Passwort wiederholen</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Passwort zurücksetzen</button>
                    <a href="../" class="btn btn-secondary">Zurück</a>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Passwort-Stärke-Balken
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('password-strength-bar');

        passwordInput.addEventListener('input', function() {
            const value = passwordInput.value;
            let strength = 0;

            if (value.length >= 8) strength += 1;
            if (/[A-Z]/.test(value)) strength += 1;
            if (/[a-z]/.test(value)) strength += 1;
            if (/\d/.test(value)) strength += 1;
            if (/[\W_]/.test(value)) strength += 1;

            switch (strength) {
                case 0:
                case 1:
                    strengthBar.style.width = '20%';
                    strengthBar.style.backgroundColor = 'red';
                    break;
                case 2:
                    strengthBar.style.width = '40%';
                    strengthBar.style.backgroundColor = 'orange';
                    break;
                case 3:
                    strengthBar.style.width = '60%';
                    strengthBar.style.backgroundColor = 'yellow';
                    break;
                case 4:
                    strengthBar.style.width = '80%';
                    strengthBar.style.backgroundColor = 'lightgreen';
                    break;
                case 5:
                    strengthBar.style.width = '100%';
                    strengthBar.style.backgroundColor = 'green';
                    break;
            }
        });
    </script>
</body>
</html>

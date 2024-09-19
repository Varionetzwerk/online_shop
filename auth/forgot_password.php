<?php
session_start();
include('../includes/db.php'); // Verbindung zur Datenbank herstellen
require_once '../config.php'; // Pfad zur config.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Lade PHPMailer Bibliothek
require '../vendor/autoload.php'; // Passe den Pfad zur autoload.php von PHPMailer an

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email'])) {
        $email = htmlspecialchars(trim($_POST['email']));

        // Prüfen, ob die E-Mail existiert
        $sql = "SELECT id, username FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($userId, $username);
            $stmt->fetch();

            $reset_code = bin2hex(random_bytes(16));
            $expiration = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Reset-Code und Ablaufdatum in die Datenbank einfügen
            $sql = "INSERT INTO password_reset (email, reset_code, expiration) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE reset_code = VALUES(reset_code), expiration = VALUES(expiration)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sss', $email, $reset_code, $expiration);
            $stmt->execute();

            // E-Mail senden
            $reset_link = "http://localhost/online-shop/auth/reset_password.php?code=" . $reset_code . "&email=" . urlencode($email);
            $message = "<p>Hallo $username,</p>
                        <p>Sie haben eine Anfrage zum Zurücksetzen Ihres Passworts gestellt. Klicken Sie auf den folgenden Link, um Ihr Passwort zurückzusetzen:</p>
                        <p><a href=\"$reset_link\">Passwort zurücksetzen</a></p>
                        <p>Wenn Sie diese Anfrage nicht gestellt haben, ignorieren Sie bitte diese E-Mail.</p>
                        <p>Mit freundlichen Grüßen,<br>Ihr Team</p>";

            if (sendResetEmail($email, 'Passwort zurücksetzen', $message)) {
                echo "<div class='alert alert-success'>Eine E-Mail zum Zurücksetzen des Passworts wurde gesendet.</div>";
            } else {
                echo "<div class='alert alert-danger'>Fehler beim Senden der E-Mail.</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Diese E-Mail-Adresse ist nicht registriert.</div>";
        }

        $stmt->close();
    }
}

// Funktion zum Senden von E-Mails über SMTP
function sendResetEmail($to, $subject, $message) {
    $mail = new PHPMailer(true); // Instanzierung der PHPMailer-Klasse

    try {
        // Server-Einstellungen
        $mail->isSMTP(); // Setze den Mailer auf SMTP
        $mail->Host       = SMTP_HOST; // SMTP-Server-Adresse
        $mail->SMTPAuth   = true; // SMTP Authentifizierung aktivieren
        $mail->Username   = SMTP_USERNAME; // SMTP-Benutzername
        $mail->Password   = SMTP_PASSWORD; // SMTP-Passwort
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Verschlüsselung aktivieren
        $mail->Port       = SMTP_PORT; // SMTP-Port

        // Absender
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($to); // Empfänger

        // Inhalt
        $mail->isHTML(true); // Setze das Format auf HTML
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
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
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Passwort vergessen</h2>
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <form method="post" action="">
                    <div class="form-group">
                        <label for="email">E-Mail-Adresse</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Passwort zurücksetzen</button>
                    <a href="../" class="btn btn-secondary">Zurück</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

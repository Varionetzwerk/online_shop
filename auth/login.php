<?php
session_start();
include('../includes/db.php');
require_once '../config.php'; // Pfad zur config.php

$hcaptchaSiteKey = HCAPTCHA_SITE_KEY;
$hcaptchaSecretKey = HCAPTCHA_SECRET_KEY;

// Sicherheitsmaßnahmen
$maxAttempts = 5; // Maximale Versuche
$lockoutTime = 5 * 60; // Sperrzeit in Sekunden (5 Minuten)

// CSRF-Token generieren
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// hCaptcha Secret Key
$hcaptchaSecret = HCAPTCHA_SECRET_KEY;

// Prüfen, ob ein POST-Request vorliegt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF-Token überprüfen
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Ungültiger CSRF-Token');
    }

    $email = htmlspecialchars(trim($_POST['email']));
    $password = htmlspecialchars(trim($_POST['password']));
    $hcaptchaResponse = isset($_POST['h-captcha-response']) ? $_POST['h-captcha-response'] : '';

    // Überprüfen der hCaptcha-Antwort
    $hcaptchaVerify = file_get_contents("https://hcaptcha.com/siteverify?secret=$hcaptchaSecret&response=$hcaptchaResponse");
    $hcaptchaVerify = json_decode($hcaptchaVerify);

    if (!$hcaptchaVerify->success) {
        $error = 'Bitte bestätigen Sie, dass Sie kein Roboter sind.';
    } else {
        if (isset($_POST['login'])) {
            // Login-Logik
            $stmt = $conn->prepare("
                SELECT id, password, role, failed_attempts, unlock_time, ban_reason, ban_until, banned_by 
                FROM users 
                WHERE email = ?
            ");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                $stmt->bind_result($userId, $hashedPassword, $userRole, $failedAttempts, $unlockTime, $banReason, $banUntil, $bannedBy);
                $stmt->fetch();
                
                $now = time(); // Aktuelle Zeit

                // Berechnungen für Bann-Dauer
                if (!empty($banUntil) && is_numeric($banUntil)) {
                    $banUntil = intval($banUntil); // Unix-Timestamp direkt nutzen

                    if ($banUntil > $now) {
                        $diff = $banUntil - $now;

                        // Berechne die verbleibende Zeit in Jahren, Monaten, Wochen, Tagen, Stunden und Minuten
                        $years = floor($diff / (365 * 24 * 60 * 60));
                        $months = floor(($diff % (365 * 24 * 60 * 60)) / (30 * 24 * 60 * 60));
                        $weeks = floor(($diff % (30 * 24 * 60 * 60)) / (7 * 24 * 60 * 60));
                        $days = floor(($diff % (7 * 24 * 60 * 60)) / (24 * 60 * 60));
                        $hours = floor(($diff % (24 * 60 * 60)) / (60 * 60));
                        $minutes = floor(($diff % (60 * 60)) / 60);

                        $banDuration = "$years Jahre, $months Monate, $weeks Wochen, $days Tage, $hours Stunden, $minutes Minuten";
                    } else {
                        $banDuration = "Bann abgelaufen";
                    }
                } else {
                    $banDuration = "Unbegrenzt";
                }

                if ($banUntil > $now || ($failedAttempts >= $maxAttempts && $unlockTime > $now)) {
                    // Holen Sie den Benutzernamen des Benutzers, der die Sperrung vorgenommen hat
                    if (!empty($bannedBy) && is_numeric($bannedBy)) {
                        $stmtUser = $conn->prepare("SELECT username FROM users WHERE id = ?");
                        $stmtUser->bind_param("i", $bannedBy);
                        $stmtUser->execute();
                        $resultUser = $stmtUser->get_result();
                        $admin = $resultUser->fetch_assoc();
                        $bannedByName = $admin ? htmlspecialchars($admin['username']) : 'Unbekannt';
                        $stmtUser->close(); // Statement schließen
                    } else {
                        $bannedByName = 'Unbekannt';
                    }

                    $stmt->close(); // Statement schließen
                    $error = 'Ihr Konto ist gesperrt. Grund: ' . htmlspecialchars($banReason) . '. Sperrzeit: ' . $banDuration . '. Gesperrt von: ' . $bannedByName . '.';
                } elseif (password_verify($password, $hashedPassword)) {
                    // Erfolgreiches Login
                    $_SESSION['user_id'] = (int)$userId; // user_id als Ganzzahl speichern
                    $_SESSION['user_role'] = $userRole;

                    // Online-Status setzen
                    $datetime = date('Y-m-d H:i:s', $now); // Unix-Timestamp in DATETIME umwandeln
                    $stmtUpdate = $conn->prepare("UPDATE users SET last_activity = ?, status = 'online' WHERE id = ?");
                    $stmtUpdate->bind_param('si', $datetime, $userId);
                    $stmtUpdate->execute();
                    $stmtUpdate->close(); // Statement schließen

                    $stmtUpdate = $conn->prepare("UPDATE users SET failed_attempts = 0, unlock_time = 0 WHERE email = ?");
                    $stmtUpdate->bind_param('s', $email);
                    $stmtUpdate->execute();
                    $stmtUpdate->close(); // Statement schließen
                    header('Location: ../');
                    exit();
                } else {
                    // Falsches Passwort, Anzahl der Versuche erhöhen
                    $failedAttempts++;
                    if ($failedAttempts >= $maxAttempts) {
                        // Benutzerkonto sperren und Unix-Timestamp für Entsperrzeit setzen
                        $unlockTime = $now + $lockoutTime;
                        $stmtUpdate = $conn->prepare("UPDATE users SET failed_attempts = ?, unlock_time = ? WHERE email = ?");
                        $stmtUpdate->bind_param('iis', $failedAttempts, $unlockTime, $email);
                    } else {
                        // Nur Anzahl der fehlgeschlagenen Versuche aktualisieren
                        $stmtUpdate = $conn->prepare("UPDATE users SET failed_attempts = ? WHERE email = ?");
                        $stmtUpdate->bind_param('is', $failedAttempts, $email);
                    }
                    $stmtUpdate->execute();
                    $stmtUpdate->close(); // Statement schließen

                    $remainingAttempts = $maxAttempts - $failedAttempts;
                    if ($remainingAttempts > 0) {
                        $error = 'Falsches Passwort. Versuche verbleibend: ' . $remainingAttempts;
                    } else {
                        $error = 'Zu viele fehlgeschlagene Versuche. Ihr Konto wurde gesperrt.';
                    }
                }
            } else {
                $error = 'Benutzer existiert nicht.';
            }
        }

        if (isset($_POST['register'])) {
            // Registrierungs-Logik
            $username = htmlspecialchars(trim($_POST['username']));
            $email = htmlspecialchars(trim($_POST['email']));
            $password = htmlspecialchars(trim($_POST['password']));

            // Überprüfen, ob die E-Mail bereits existiert
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $error = "E-Mail-Adresse bereits vergeben.";
            } else {
                // Passwortstärke überprüfen
                if (preg_match('/^(?=.*\d)(?=.*[!@#$%^&*])(?=.*[a-z])(?=.*[A-Z]).{8,}$/', $password)) {
                    // Passwort hashen
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                    // Benutzer in die Datenbank einfügen
                    $stmt = $conn->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
                    $stmt->bind_param('sss', $username, $hashed_password, $email);
                    $stmt->execute();
                    $userId = $stmt->insert_id;
                    $stmt->close(); // Statement schließen

                    // Generiere eine einzigartige user_id
                    $uniqueUserId = $userId; // Für einfaches Beispiel: user_id ist dieselbe wie id

                    // user_id aktualisieren
                    $stmtUpdate = $conn->prepare("UPDATE users SET user_id = ? WHERE id = ?");
                    $stmtUpdate->bind_param('ii', $uniqueUserId, $userId);
                    $stmtUpdate->execute();
                    $stmtUpdate->close(); // Statement schließen

                    // Weiterleitung zur Login-Seite nach erfolgreicher Registrierung
                    header('Location: login.php');
                    exit();
                } else {
                    $error = "Passwort muss mindestens 8 Zeichen lang sein und eine Zahl sowie ein Sonderzeichen enthalten.";
                }
            }
            $stmt->close(); // Statement schließen
        }
    }
}
?>




<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
    <h2></h2>
    <div class="container" id="container">
        <!-- Registrierungsformular -->
        <div class="form-container sign-up-container">
            <form method="post" action="">
                <h1>Account erstellen</h1>
                <input type="text" id="username" name="username" placeholder="Name" required>
                <input type="email" id="email" name="email" placeholder="E-Mail" required>
                <input type="password" id="password" name="password" placeholder="Passwort" required>
                <!-- hCaptcha Widget -->
                <div class="h-captcha" data-sitekey="<?php echo HCAPTCHA_SITE_KEY; ?>"></div>
                <button type="submit" name="register">Registrieren</button>
                <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            </form>
        </div>
        
        <!-- Loginformular -->
        <div class="form-container sign-in-container">
            <form method="post" action="">
                <h1>Anmelden</h1>
                <input type="email" placeholder="E-Mail" id="email" name="email" required>
                <input type="password" placeholder="Passwort" id="password" name="password" required>
                <!-- hCaptcha Widget -->
                <div class="h-captcha" data-sitekey="<?php echo HCAPTCHA_SITE_KEY; ?>"></div>
                <a href="forgot_password.php">Passwort vergessen?</a>
                <button type="submit" name="login">Anmelden</button>
                <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            </form>
        </div>

        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h1>Willkommen zurück!</h1>
                    <p>Wenn Sie bereits ein Konto haben, melden Sie sich hier an.</p>
                    <button class="ghost" id="signIn">Anmelden</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h1>Erstellen Sie ein neues Konto</h1>
                    <p>Geben Sie Ihre Daten ein und starten Sie noch heute!</p>
                    <button class="ghost" id="signUp">Registrieren</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://hcaptcha.com/1/api.js" async defer></script>
    <script src="../assets/js/script.js"></script>
</body>
</html>


<script>
    // script.js

document.getElementById('signIn').addEventListener('click', function () {
    document.querySelector('.container').classList.remove('right-panel-active');
});

document.getElementById('signUp').addEventListener('click', function () {
    document.querySelector('.container').classList.add('right-panel-active');
});

</script>


<style>

@import url('https://fonts.googleapis.com/css?family=Montserrat:400,800');

* {
	box-sizing: border-box;
}

body {
	background: #f6f5f7;
	display: flex;
	justify-content: center;
	align-items: center;
	flex-direction: column;
	font-family: 'Montserrat', sans-serif;
	height: 100vh;
	margin: -20px 0 50px;
}

h1 {
	font-weight: bold;
	margin: 0;
}

h2 {
	text-align: center;
}

p {
	font-size: 14px;
	font-weight: 100;
	line-height: 20px;
	letter-spacing: 0.5px;
	margin: 20px 0 30px;
}

span {
	font-size: 12px;
}

a {
	color: #333;
	font-size: 14px;
	text-decoration: none;
	margin: 15px 0;
}

button {
	border-radius: 20px;
	border: 1px solid #FF4B2B;
	background-color: #FF4B2B;
	color: #FFFFFF;
	font-size: 12px;
	font-weight: bold;
	padding: 12px 45px;
	letter-spacing: 1px;
	text-transform: uppercase;
	transition: transform 80ms ease-in;
}

button:active {
	transform: scale(0.95);
}

button:focus {
	outline: none;
}

button.ghost {
	background-color: transparent;
	border-color: #FFFFFF;
}

form {
	background-color: #FFFFFF;
	display: flex;
	align-items: center;
	justify-content: center;
	flex-direction: column;
	padding: 0 50px;
	height: 100%;
	text-align: center;
}

input {
	background-color: #eee;
	border: none;
	padding: 12px 15px;
	margin: 8px 0;
	width: 100%;
}

.container {
	background-color: #fff;
	border-radius: 10px;
  	box-shadow: 0 14px 28px rgba(0,0,0,0.25), 
			0 10px 10px rgba(0,0,0,0.22);
	position: relative;
	overflow: hidden;
	width: 768px;
	max-width: 100%;
	min-height: 480px;
}

.form-container {
	position: absolute;
	top: 0;
	height: 100%;
	transition: all 0.6s ease-in-out;
}

.sign-in-container {
	left: 0;
	width: 50%;
	z-index: 2;
}

.container.right-panel-active .sign-in-container {
	transform: translateX(100%);
}

.sign-up-container {
	left: 0;
	width: 50%;
	opacity: 0;
	z-index: 1;
}

.container.right-panel-active .sign-up-container {
	transform: translateX(100%);
	opacity: 1;
	z-index: 5;
	animation: show 0.6s;
}

@keyframes show {
	0%, 49.99% {
		opacity: 0;
		z-index: 1;
	}
	
	50%, 100% {
		opacity: 1;
		z-index: 5;
	}
}

.overlay-container {
	position: absolute;
	top: 0;
	left: 50%;
	width: 50%;
	height: 100%;
	overflow: hidden;
	transition: transform 0.6s ease-in-out;
	z-index: 100;
}

.container.right-panel-active .overlay-container{
	transform: translateX(-100%);
}

.overlay {
	background: #FF416C;
	background: -webkit-linear-gradient(to right, #FF4B2B, #FF416C);
	background: linear-gradient(to right, #FF4B2B, #FF416C);
	background-repeat: no-repeat;
	background-size: cover;
	background-position: 0 0;
	color: #FFFFFF;
	position: relative;
	left: -100%;
	height: 100%;
	width: 200%;
  	transform: translateX(0);
	transition: transform 0.6s ease-in-out;
}

.container.right-panel-active .overlay {
  	transform: translateX(50%);
}

.overlay-panel {
	position: absolute;
	display: flex;
	align-items: center;
	justify-content: center;
	flex-direction: column;
	padding: 0 40px;
	text-align: center;
	top: 0;
	height: 100%;
	width: 50%;
	transform: translateX(0);
	transition: transform 0.6s ease-in-out;
}

.overlay-left {
	transform: translateX(-20%);
}

.container.right-panel-active .overlay-left {
	transform: translateX(0);
}

.overlay-right {
	right: 0;
	transform: translateX(0);
}

.container.right-panel-active .overlay-right {
	transform: translateX(20%);
}

.social-container {
	margin: 20px 0;
}

.social-container a {
	border: 1px solid #DDDDDD;
	border-radius: 50%;
	display: inline-flex;
	justify-content: center;
	align-items: center;
	margin: 0 5px;
	height: 40px;
	width: 40px;
}

footer {
    background-color: #222;
    color: #fff;
    font-size: 14px;
    bottom: 0;
    position: fixed;
    left: 0;
    right: 0;
    text-align: center;
    z-index: 999;
}

footer p {
    margin: 10px 0;
}

footer i {
    color: red;
}

footer a {
    color: #3c97bf;
    text-decoration: none;
}

</style>
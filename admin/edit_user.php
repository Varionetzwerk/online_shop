<?php
session_start();
include('../user/update_status.php'); // Statusaktualisierung inkludieren

// Überprüfen, ob der Benutzer angemeldet ist und Admin oder Moderator ist
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && $_SESSION['user_role'] === 'admin';
$isModerator = $isLoggedIn && $_SESSION['user_role'] === 'moderator';

if (!$isLoggedIn || (!$isAdmin && !$isModerator)) {
    die('Zugriff verweigert');
}

// Konfigurations- und Verbindungsdateien einbinden
include('../includes/db.php'); // Pfad zur db.php im includes-Verzeichnis

if (!isset($conn)) {
    die("Datenbankverbindung fehlgeschlagen.");
}

// Benutzer-ID aus der URL holen
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id <= 0) {
    die('Ungültige Benutzer-ID');
}

// Bereinigen von abgelaufenen Banns
$now = time(); // Aktuelle Zeit als Unix-Timestamp
$stmt = $conn->prepare("UPDATE users SET ban_reason = NULL, ban_until = NULL WHERE ban_until IS NOT NULL AND ban_until <= ?");
$stmt->bind_param("i", $now);
$stmt->execute();
$stmt->close();

// Daten des Benutzers abrufen, einschließlich des Admin-Namens, der den Bann verhängt hat
$query = "
    SELECT u.username, u.email, u.role, u.address, u.city, u.street, u.house_number, u.postal_code, 
           u.ban_reason, u.ban_until, u.banned_by, a.username AS banned_by_admin
    FROM users u
    LEFT JOIN users a ON u.banned_by = a.user_id
    WHERE u.user_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die('Benutzer nicht gefunden');
}

// Nachrichten initialisieren
$successMessage = '';
$errorMessage = '';

// Daten aktualisieren, wenn das Formular gesendet wurde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['unban_user'])) {
        // Entbannungs-Logik
        $stmt = $conn->prepare("
            UPDATE users 
            SET ban_reason = NULL, ban_until = NULL, banned_by = NULL
            WHERE user_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $successMessage = "Benutzer erfolgreich entbannt.";
        } else {
            $errorMessage = "Fehler beim Entbannen des Benutzers: " . $stmt->error;
        }
        $stmt->close();
    } else {
        // Sichern der Formulardaten
        $username = isset($_POST['username']) ? $_POST['username'] : $user['username'];
        $email = isset($_POST['email']) ? $_POST['email'] : $user['email'];
        $role = isset($_POST['role']) ? $_POST['role'] : $user['role'];
        $address = isset($_POST['address']) ? $_POST['address'] : $user['address'];
        $city = isset($_POST['city']) ? $_POST['city'] : $user['city'];
        $street = isset($_POST['street']) ? $_POST['street'] : $user['street'];
        $house_number = isset($_POST['house_number']) ? $_POST['house_number'] : $user['house_number'];
        $postal_code = isset($_POST['postal_code']) ? $_POST['postal_code'] : $user['postal_code'];

        // Bann-Grund und -Dauer
        $ban_reason = isset($_POST['ban_reason']) ? $_POST['ban_reason'] : NULL;
        if ($ban_reason === 'Andere') {
            $ban_reason = isset($_POST['custom_ban_reason']) ? $_POST['custom_ban_reason'] : NULL; // Benutzerdefinierter Bann-Grund
        }

        // Prüfen, ob ein Bann-Grund angegeben wurde, wenn ein Bann-Grund gesetzt werden soll
        if ($ban_reason && empty($ban_reason)) {
            $errorMessage = 'Ein Bann-Grund muss angegeben werden.';
        } else {
            $ban_until = !empty($_POST['ban_until']) ? strtotime($_POST['ban_until']) : NULL;

            // Prüfen, ob das Datum in der Zukunft liegt
            if ($ban_until !== NULL && $ban_until <= $now) {
                $errorMessage = 'Das Bann-Datum ist ungültig. Bitte wählen Sie ein zukünftiges Datum.';
            } else {
                // Setze `banned_by` nur, wenn ein Bann-Grund und -Datum vorhanden sind
                $banned_by = ($ban_reason && $ban_until) ? $_SESSION['user_id'] : NULL;

                // Benutzer aktualisieren
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET username = ?, email = ?, role = ?, address = ?, city = ?, street = ?, house_number = ?, postal_code = ?, 
                        ban_reason = ?, ban_until = ?, banned_by = ?
                    WHERE user_id = ?
                ");
                $stmt->bind_param(
                    "ssssssssssii", 
                    $username, 
                    $email, 
                    $role, 
                    $address, 
                    $city, 
                    $street, 
                    $house_number, 
                    $postal_code, 
                    $ban_reason, 
                    $ban_until,
                    $banned_by,
                    $user_id
                );

                if ($stmt->execute()) {
                    $successMessage = "Profil von $username erfolgreich aktualisiert.";
                } else {
                    $errorMessage = "Fehler beim Aktualisieren des Benutzers: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benutzer bearbeiten</title>
    <link rel="stylesheet" href="../assets/css/edit_user.css?v=1.0">
    <style>
        /* Styles für Modal */
        .modal {
            display: none; 
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            border-radius: 8px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        /* Styles für Buttons */
        .btn {
            background-color: #4CAF50; /* Green */
            border: none;
            color: white;
            padding: 15px 32px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 8px;
        }

        .btn-update {
            background-color: #008CBA; /* Blue */
        }

        .btn-unban {
            background-color: #f44336; /* Red */
        }

        .btn-back {
            background-color: #555555; /* Dark Gray */
        }
        
        .btn:hover {
            opacity: 0.8;
        }

        /* Style für Date-Time Picker */
        .datetime-picker {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        /* Styles für Fehler- und Erfolgsmeldungen */
        .error-message {
            color: red;
            font-weight: bold;
        }

        .success-message {
            color: green;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Benutzer bearbeiten</h1>

    <?php if ($successMessage): ?>
        <p class="success-message"><?php echo htmlspecialchars($successMessage); ?></p>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <p class="error-message"><?php echo htmlspecialchars($errorMessage); ?></p>
    <?php endif; ?>

    <form action="edit_user.php?id=<?php echo htmlspecialchars($user_id); ?>" method="post">
        <label for="username">Benutzername:</label>
        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>"><br>

        <label for="email">E-Mail:</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>"><br>

        <label for="role">Rolle:</label>
        <select id="role" name="role">
            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
            <option value="moderator" <?php echo $user['role'] === 'moderator' ? 'selected' : ''; ?>>Moderator</option>
            <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>Benutzer</option>
        </select><br>

        <label for="address">Adresse:</label>
        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address']); ?>"><br>

        <label for="city">Stadt:</label>
        <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($user['city']); ?>"><br>

        <label for="street">Straße:</label>
        <input type="text" id="street" name="street" value="<?php echo htmlspecialchars($user['street']); ?>"><br>

        <label for="house_number">Hausnummer:</label>
        <input type="text" id="house_number" name="house_number" value="<?php echo htmlspecialchars($user['house_number']); ?>"><br>

        <label for="postal_code">Postleitzahl:</label>
        <input type="text" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($user['postal_code']); ?>"><br>

        <?php if ($isAdmin || $isModerator): ?>
            <button type="button" class="btn" onclick="openBanModal()">Bannen</button>
        <?php endif; ?>

        <?php if ($user['ban_until'] && $user['ban_until'] > $now): ?>
            <button type="submit" name="unban_user" class="btn btn-unban">Entbannen</button>
        <?php endif; ?>

        <button type="submit" name="update_user" class="btn btn-update">Aktualisieren</button>
        <a href="dashboard.php" class="btn btn-back">Zurück zur Benutzerliste</a>
    </form>

    <!-- Modal für Bann -->
    <div id="banModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeBanModal()">&times;</span>
            <h2>Bann-Einstellungen</h2>
            <form action="edit_user.php?id=<?php echo htmlspecialchars($user_id); ?>" method="post">
                <label for="ban_reason_modal">Bann-Grund:</label>
                <select id="ban_reason_modal" name="ban_reason" onchange="toggleCustomBanReasonModal()">
                    <option value="" <?php echo empty($user['ban_reason']) ? 'selected' : ''; ?>>Bitte wählen...</option>
                    <option value="Spam" <?php echo $user['ban_reason'] === 'Spam' ? 'selected' : ''; ?>>Spam</option>
                    <option value="Hate Speech" <?php echo $user['ban_reason'] === 'Hate Speech' ? 'selected' : ''; ?>>Hate Speech</option>
                    <option value="Andere" <?php echo $user['ban_reason'] === 'Andere' ? 'selected' : ''; ?>>Andere</option>
                </select><br>

                <div id="custom_ban_reason_div_modal" style="display: <?php echo $user['ban_reason'] === 'Andere' ? 'block' : 'none'; ?>;">
                    <label for="custom_ban_reason_modal">Benutzerdefinierter Bann-Grund:</label>
                    <input type="text" id="custom_ban_reason_modal" name="custom_ban_reason" value="<?php echo htmlspecialchars($user['ban_reason'] === 'Andere' ? $user['ban_reason'] : ''); ?>"><br>
                </div>

                <label for="ban_until_modal">Bann-Datum und Uhrzeit:</label>
                <input type="datetime-local" id="ban_until_modal" name="ban_until" class="datetime-picker" value="<?php echo htmlspecialchars($user['ban_until'] ? date('Y-m-d\TH:i', $user['ban_until']) : date('Y-m-d\TH:i', strtotime('+1 day'))); ?>"><br>

                <button type="submit" class="btn">Bannen</button>
            </form>
        </div>
    </div>

    <script>
        function toggleCustomBanReason() {
            var selectElement = document.getElementById('ban_reason');
            var customBanReasonDiv = document.getElementById('custom_ban_reason_div');
            if (selectElement.value === 'Andere') {
                customBanReasonDiv.style.display = 'block';
            } else {
                customBanReasonDiv.style.display = 'none';
            }
        }

        function toggleCustomBanReasonModal() {
            var selectElement = document.getElementById('ban_reason_modal');
            var customBanReasonDiv = document.getElementById('custom_ban_reason_div_modal');
            if (selectElement.value === 'Andere') {
                customBanReasonDiv.style.display = 'block';
            } else {
                customBanReasonDiv.style.display = 'none';
            }
        }

        function openBanModal() {
            document.getElementById('banModal').style.display = 'block';
            toggleCustomBanReasonModal();
        }

        function closeBanModal() {
            document.getElementById('banModal').style.display = 'none';
        }

        // Init
        document.addEventListener('DOMContentLoaded', function() {
            toggleCustomBanReason();
        });
    </script>
</body>
</html>

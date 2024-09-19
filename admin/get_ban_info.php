<?php
session_start();

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

// Daten des Benutzers abrufen
$stmt = $conn->prepare("SELECT username, ban_reason, ban_until, banned_by FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die('Benutzer nicht gefunden');
}

// Berechnungen für Bann-Dauer
$now = time(); // Aktuelle Zeit

// Überprüfung, ob `ban_until` existiert und ob es ein Unix-Timestamp ist
if (!empty($user['ban_until']) && is_numeric($user['ban_until'])) {
    $ban_until = intval($user['ban_until']); // Unix-Timestamp direkt nutzen
    
    // Wenn der Bann noch aktiv ist (Bann-Datum ist in der Zukunft)
    if ($ban_until > $now) {
        $diff = $ban_until - $now;

        // Berechne die verbleibende Zeit in Jahren, Monaten, Wochen, Tagen, Stunden und Minuten
        $years = floor($diff / (365 * 24 * 60 * 60));
        $months = floor(($diff % (365 * 24 * 60 * 60)) / (30 * 24 * 60 * 60));
        $weeks = floor(($diff % (30 * 24 * 60 * 60)) / (7 * 24 * 60 * 60));
        $days = floor(($diff % (7 * 24 * 60 * 60)) / (24 * 60 * 60));
        $hours = floor(($diff % (24 * 60 * 60)) / (60 * 60));
        $minutes = floor(($diff % (60 * 60)) / 60);
        
        // Ausgabe der verbleibenden Zeit
        $ban_duration = "$years Jahre, $months Monate, $weeks Wochen, $days Tage, $hours Stunden, $minutes Minuten";
    } else {
        // Der Bann ist bereits abgelaufen
        $ban_duration = "Bann abgelaufen";
    }
} else {
    // Kein Bann-Datum angegeben oder kein gültiger Unix-Timestamp
    $ban_duration = "Unbegrenzt";
}

// Gebannt von Admin ermitteln
if (!empty($user['banned_by'])) {
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $user['banned_by']);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    $stmt->close();

    $banned_by_name = $admin ? $admin['username'] : 'Unbekannt';
} else {
    $banned_by_name = 'Unbekannt';
}

// Ban-Info zurückgeben
echo "<p><strong>Benutzername:</strong> " . htmlspecialchars($user['username']) . "</p>";
echo "<p><strong>Bann-Grund:</strong> " . htmlspecialchars($user['ban_reason']) . "</p>";
echo "<p><strong>Bann-Dauer:</strong> " . htmlspecialchars($ban_duration) . "</p>";
echo "<p><strong>Gebannt von:</strong> " . htmlspecialchars($banned_by_name) . "</p>";
?>

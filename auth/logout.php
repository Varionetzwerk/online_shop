<?php
session_start();
include('../includes/db.php'); // Pfad zum db.php im includes-Verzeichnis

if (!isset($conn)) {
    die("Datenbankverbindung fehlgeschlagen.");
}

// Prüfen, ob der Benutzer angemeldet ist
if (isset($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];
    
    // Status auf "offline" setzen
    $datetime = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("UPDATE users SET status = 'offline', last_activity = ? WHERE id = ?");
    $stmt->bind_param('si', $datetime, $userId);
    $stmt->execute();
    $stmt->close(); // Statement schließen
    
    // Sitzung beenden
    session_unset();
    session_destroy();
    
    // Zurück zur Startseite oder Login-Seite weiterleiten
    header('Location: ../index.php');
    exit();
} else {
    // Benutzer ist nicht eingeloggt, also weiter zur Startseite
    header('Location: ../index.php');
    exit();
}
?>

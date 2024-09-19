<?php
session_start();

// Datenbankverbindung einbinden
include('../includes/db.php');

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $lastActivity = isset($_POST['last_activity']) ? (int)$_POST['last_activity'] : time(); // Stelle sicher, dass es ein Integer ist

    // Unix-Zeitstempel in MySQL DATETIME Format umwandeln
    $lastActivityDatetime = date('Y-m-d H:i:s', $lastActivity);

    // Bereite die SQL-Anweisung vor und binde Parameter
    $stmt = $conn->prepare("UPDATE users SET last_activity = ? WHERE id = ?");
    $stmt->bind_param('si', $lastActivityDatetime, $userId);
    $stmt->execute();
    $stmt->close();
}
?>

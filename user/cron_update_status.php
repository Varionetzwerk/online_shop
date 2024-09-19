<?php
include('../includes/db.php');

$now = time();
$awayTime = 5 * 60; // 5 Minuten

// Benutzer, die länger als 5 Minuten inaktiv sind, auf 'away' setzen
$stmt = $conn->prepare("UPDATE users SET status = 'away' WHERE status = 'online' AND TIMESTAMPDIFF(SECOND, last_activity, NOW()) > ?");
$stmt->bind_param('i', $awayTime);
$stmt->execute();
$stmt->close();
?>

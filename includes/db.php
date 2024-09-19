<?php
$servername = "localhost"; // Dein Servername
$username = "root";        // Dein Datenbankbenutzername
$password = "";            // Dein Datenbankpasswort
$dbname = "online_shop";   // Name deiner Datenbank

// Erstelle Verbindung
$conn = new mysqli($servername, $username, $password, $dbname);

// Überprüfe Verbindung
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}
?>

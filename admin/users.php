<?php
session_start();
include('../includes/db.php'); // Pfad zum db.php im includes-Verzeichnis

if (!isset($conn)) {
    die("Datenbankverbindung fehlgeschlagen.");
}

// Prüfen, ob der Benutzer angemeldet ist und Admin ist
$isLoggedIn = isset($_SESSION['user']);
$isAdmin = $isLoggedIn && $_SESSION['user_role'] === 'admin';

if (!$isAdmin) {
    echo "<p>Zugriff verweigert.</p>";
    exit();
}

// Benutzerverwaltung
$users = [];
$sql = "SELECT * FROM users";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $users = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<h2>Benutzerverwaltung</h2>
<table class="table table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Benutzername</th>
            <th>E-Mail</th>
            <th>Rolle</th>
            <th>Aktionen</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo htmlspecialchars($user['id']); ?></td>
                <td><?php echo htmlspecialchars($user['username']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td><?php echo htmlspecialchars($user['role']); ?></td>
                <td>
                    <!-- Hier könnten weitere Aktionen wie Bann- oder Bearbeitungsfunktionen hinzugefügt werden -->
                    <a href="admin/ban_user.php?id=<?php echo htmlspecialchars($user['id']); ?>" class="btn btn-danger btn-sm"><i class="fas fa-ban"></i> Bann</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

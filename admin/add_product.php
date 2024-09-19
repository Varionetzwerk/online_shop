<?php
session_start();
include('../includes/db.php'); // Pfad zum db.php im includes-Verzeichnis

if (!isset($conn)) {
    die("Datenbankverbindung fehlgeschlagen.");
}

// Prüfen, ob der Benutzer angemeldet ist und Admin ist
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
$isModerator = $isLoggedIn && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'moderator';

if (!$isAdmin) {
    header('Location: ../index.php');
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $image = $_FILES['image']['name'];
    $target_dir = "../assets/images/"; // Pfad zum Upload-Verzeichnis
    $target_file = $target_dir . basename($image);

    // Validierung
    if (empty($name) || empty($price) || empty($description) || empty($image)) {
        $error = 'Bitte füllen Sie alle Felder aus.';
    } elseif (!is_numeric($price)) {
        $error = 'Der Preis muss eine gültige Zahl sein.';
    } else {
        // Bild hochladen
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            // Produkt in der Datenbank speichern
            $stmt = $conn->prepare("INSERT INTO products (name, price, description, images) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sdss", $name, $price, $description, $image);
            if ($stmt->execute()) {
                $success = 'Produkt erfolgreich hinzugefügt!';
            } else {
                $error = 'Fehler beim Hinzufügen des Produkts. Bitte versuchen Sie es später erneut.';
            }
            $stmt->close();
        } else {
            $error = 'Fehler beim Hochladen des Bildes.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produkt hinzufügen</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        header {
            background-color: #343a40;
            color: #ffffff;
            padding: 15px 0;
        }
        header h1 {
            margin: 0;
        }
        .btn-light {
            color: #343a40;
            border-color: #ffffff;
        }
        .btn-light:hover {
            background-color: #e2e6ea;
        }
        .container {
            margin-top: 20px;
        }
        footer {
            background-color: #343a40;
            color: #ffffff;
            padding: 20px 0;
            text-align: center;
            position: fixed;
            bottom: 0;
            width: 100%;
        }
        .alert {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <header class="bg-dark text-white py-3">
        <div class="container">
            <h1 class="h3">Produkt hinzufügen</h1>
            <a href="../index.php" class="btn btn-light"><i class="fas fa-home"></i> Zurück zur Startseite</a>
            <a href="dashboard.php" class="btn btn-light"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="../logout.php" class="btn btn-light"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>

    <main class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php elseif ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="name">Produktname</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="price">Preis (in €)</label>
                                <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                            </div>
                            <div class="form-group">
                                <label for="description">Beschreibung</label>
                                <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                            </div>
                            <div class="form-group">
                                <label for="image">Produktbild</label>
                                <input type="file" class="form-control-file" id="image" name="image" accept="image/*" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Produkt hinzufügen</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Online Shop</p>
    </footer>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

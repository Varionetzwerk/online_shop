<?php
session_start();
include('../includes/db.php'); // Pfad zur Datenbankverbindung

// Prüfen, ob der Benutzer angemeldet ist
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Benutzer-ID aus der Session holen
$user_id = $_SESSION['user_id'];

// Benutzerinformationen abrufen
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Wenn der Benutzer seine Daten aktualisieren möchte
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sicherstellen, dass POST-Daten vorhanden sind
    $name = isset($_POST['username']) ? trim($_POST['username']) : $user['username'];
    $email = isset($_POST['email']) ? trim($_POST['email']) : $user['email'];
    $postal_code = isset($_POST['postal_code']) ? trim($_POST['postal_code']) : $user['postal_code'];
    $city = isset($_POST['city']) ? trim($_POST['city']) : $user['city'];
    $street = isset($_POST['street']) ? trim($_POST['street']) : $user['street'];
    $house_number = isset($_POST['house_number']) ? trim($_POST['house_number']) : $user['house_number'];
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    $password_repeat = isset($_POST['password_repeat']) ? trim($_POST['password_repeat']) : '';

    // Passwort verschlüsseln, wenn es gesetzt ist
    $hashed_password = !empty($password) ? password_hash($password, PASSWORD_BCRYPT) : $user['password'];

    if ($password !== $password_repeat) {
        $error = "Die Passwörter stimmen nicht überein.";
    } elseif (!empty($password) && (strlen($password) < 8 || !preg_match("/[A-Z]/", $password) || !preg_match("/[a-z]/", $password) || !preg_match("/[0-9]/", $password) || !preg_match("/[\W_]/", $password))) {
        $error = "Das Passwort ist zu schwach. Es muss mindestens 8 Zeichen lang sein und einen Großbuchstaben, einen Kleinbuchstaben, eine Ziffer und ein Sonderzeichen enthalten.";
    } else {
        // Benutzerinformationen aktualisieren
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, postal_code = ?, city = ?, street = ?, house_number = ?, password = ? WHERE id = ?");
        $stmt->bind_param("sssssssi", $name, $email, $postal_code, $city, $street, $house_number, $hashed_password, $user_id);

        if ($stmt->execute()) {
            $success = "Profil erfolgreich aktualisiert.";
            // Die aktualisierten Werte in der Sitzung speichern
            $_SESSION['username'] = $name;
            $_SESSION['email'] = $email;
            $_SESSION['postal_code'] = $postal_code;
            $_SESSION['city'] = $city;
            $_SESSION['street'] = $street;
            $_SESSION['house_number'] = $house_number;

            // Die aktuellen Benutzerinformationen erneut abrufen, um sicherzustellen, dass alle Daten korrekt angezeigt werden
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } else {
            $error = "Fehler beim Aktualisieren des Profils.";
        }
    }
}

// Dynamischer Titel je nach Tageszeit
$hour = (int)date('H');
if ($hour >= 5 && $hour < 12) {
    $greeting = "Guten Morgen";
} elseif ($hour >= 12 && $hour < 18) {
    $greeting = "Guten Mittag";
} else {
    $greeting = "Guten Abend";
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f7f9;
            margin: 0;
            padding: 0;
        }
        .container {
            margin-top: 20px;
        }
        .profile-header {
            margin-bottom: 30px;
        }
        .profile-header h2 {
            font-size: 24px;
        }
        .profile-form {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .form-group label {
            font-weight: bold;
        }
        .form-control {
            margin-bottom: 15px;
        }
        .form-row {
            margin-bottom: 15px;
        }
        .form-row .form-group {
            margin-bottom: 0;
        }
        .btn-update {
            background-color: #007bff;
            color: #ffffff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-update:hover {
            background-color: #0056b3;
        }
        .alert {
            margin-top: 15px;
        }
        footer {
            background-color: #343a40;
            color: #ffffff;
            bottom: 0;
            width: 100%;
        }
        .password-strength {
            height: 5px;
            border-radius: 5px;
            background-color: #e0e0e0;
            margin-top: 5px;
            position: relative;
        }
        .password-strength-bar {
            height: 100%;
            border-radius: 5px;
            transition: width 0.3s;
        }
        .password-error {
            color: red;
            font-size: 14px;
        }
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 200px;
            background-color: #343a40;
            color: #ffffff;
            padding-top: 20px;
        }
        .sidebar a {
            color: #ffffff;
            padding: 10px 15px;
            text-decoration: none;
            display: block;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .content {
            margin-left: 220px;
        }
    </style>
</head>
<body>

    <header class="bg-dark text-white py-3">
        <div class="container">
            <h1 class="h3">Profil bearbeiten</h1>
            <a href="../" class="btn btn-light"><i class="fas fa-home"></i> Zurück zur Startseite</a>
        </div>
    </header>

    <div class="sidebar">
        <h4 class="text-center">Profil Menü</h4>
        <a href="?section=name">Name</a>
        <a href="?section=email">E-Mail</a>
        <a href="?section=address">Adresse</a>
        <a href="?section=password">Passwort</a>
    </div>

    <div class="content">
        <main class="container mt-5">
            <div class="row">
                <div class="col-md-8 mx-auto">
                    <div class="profile-header">
                        <h2><?php echo htmlspecialchars($greeting); ?>, <?php echo htmlspecialchars($user['username']); ?></h2>
                    </div>

                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php elseif (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php
                    $section = isset($_GET['section']) ? $_GET['section'] : 'name';
                    switch ($section) {
                        case 'name':
                            ?>
                            <form action="profile.php" method="POST" class="profile-form">
                                <div class="form-group">
                                    <label for="username">Benutzername</label>
                                    <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                </div>
                                <button type="submit" class="btn btn-update">Name aktualisieren</button>
                            </form>
                            <?php
                            break;
                        case 'email':
                            ?>
                            <form action="profile.php" method="POST" class="profile-form">
                                <div class="form-group">
                                    <label for="email">E-Mail-Adresse</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                <button type="submit" class="btn btn-update">E-Mail aktualisieren</button>
                            </form>
                            <?php
                            break;
                        case 'address':
                            ?>
                            <form action="profile.php" method="POST" class="profile-form">
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label for="postal_code">Postleitzahl</label>
                                        <input type="text" name="postal_code" class="form-control" value="<?php echo htmlspecialchars($user['postal_code']); ?>" required>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="city">Stadt</label>
                                        <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($user['city']); ?>" required>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <label for="street">Straße</label>
                                        <input type="text" name="street" class="form-control" value="<?php echo htmlspecialchars($user['street']); ?>" required>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="house_number">Hausnummer</label>
                                    <input type="text" name="house_number" class="form-control" value="<?php echo htmlspecialchars($user['house_number']); ?>" required>
                                </div>
                                <button type="submit" class="btn btn-update">Adresse aktualisieren</button>
                            </form>
                            <?php
                            break;
                        case 'password':
                            ?>
                            <form action="profile.php" method="POST" class="profile-form">
                                <div class="form-group">
                                    <label for="password">Neues Passwort</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="password_repeat">Neues Passwort wiederholen</label>
                                    <input type="password" name="password_repeat" class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-update">Passwort aktualisieren</button>
                            </form>
                            <?php
                            break;
                        default:
                            ?>
                            <p>Ungültiger Abschnitt.</p>
                            <?php
                            break;
                    }
                    ?>
                </div>
            </div>
        </main>
    </div>

    <footer class="py-3 text-center text-white">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Mein Online-Shop. Alle Rechte vorbehalten.</p>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

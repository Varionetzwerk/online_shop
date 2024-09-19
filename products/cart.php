<?php
session_start();
include('../includes/db.php');

if (!isset($conn)) {
    die("Datenbankverbindung fehlgeschlagen.");
}

// Warenkorb-Inhalte abrufen
$cartItems = [];
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $sql = "SELECT p.*, c.quantity FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $cartItems[] = $row;
    }
} else {
    echo "Bitte melden Sie sich an, um Ihren Warenkorb anzuzeigen.";
    exit;
}

// Gesamtpreis berechnen
$totalPrice = 0;
foreach ($cartItems as $item) {
    $totalPrice += $item['price'] * $item['quantity'];
}

// Entfernen eines Artikels aus dem Warenkorb
if (isset($_GET['remove'])) {
    $productId = (int)$_GET['remove'];
    $sql = "DELETE FROM cart WHERE user_id = ? AND product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $userId, $productId);
    $stmt->execute();
    header("Location: cart.php");
    exit;
}

$shippingCost = $totalPrice >= 50 ? 0 : 5; // Versandkosten
$totalPriceWithShipping = $totalPrice + $shippingCost;
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warenkorb</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.2.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .cart-table th, .cart-table td {
            text-align: center;
        }
        .btn-custom {
            background-color: #007bff;
            color: white;
            transition: background-color 0.3s;
        }
        .btn-custom:hover {
            background-color: #0056b3;
        }
        .sidebar {
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        footer {
            background-color: #343a40;
            color: white;
            padding: 20px 0;
        }
        footer a {
            color: #ffffff;
        }
    </style>
</head>
<body>
<header>
    <div class="header-top">
        <div class="contact-info">
            <p><i class="fa fa-envelope" aria-hidden="true"></i> info@meinewebsite.de</p>
            <p><i class="fa fa-phone" aria-hidden="true"></i> +49 123 456 789</p>
        </div>
    </div>
    <div class="header-main">
        <div class="logo">
            <a href="../index.php"><img src="../assets/images/logo.png" alt="Logo"></a>
        </div>
        <div class="search-container">
            <form action="../index.php" method="get">
                <input type="text" name="search" placeholder="Suche nach Produkten" class="form-control">
            </form>
        </div>
        <div class="nav-icons">
            <a href="cart.php" class="icon">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-count"><?php echo count($cartItems); ?></span>
            </a>
        </div>
    </div>
    <hr class="divider">
    <nav class="main-nav">
        <ul>
            <li><a href="../">Startseite</a></li>
            <li><a href="../categories.php">Kategorien</a></li>
            <li><a href="../products.php">Produkte</a></li>
            <li><a href="../contact.php">Kontakt</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="../user/profile.php">Profil</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<div class="container mt-5">
    <div class="row">
        <main class="col-md-9">
            <h1>Ihr Warenkorb</h1>
            <?php if (empty($cartItems)): ?>
                <p>Ihr Warenkorb ist leer.</p>
                <a href="../products.php" class="btn btn-custom">Weitereinkaufen</a>
            <?php else: ?>
                <table class="table cart-table">
                    <thead class="thead-dark">
                        <tr>
                            <th>Produkt</th>
                            <th>Preis</th>
                            <th>Menge</th>
                            <th>Gesamt</th>
                            <th>Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartItems as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo htmlspecialchars($item['price']); ?> €</td>
                                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                <td><?php echo htmlspecialchars($item['price'] * $item['quantity']); ?> €</td>
                                <td>
                                    <a href="cart.php?remove=<?php echo htmlspecialchars($item['id']); ?>" class="btn btn-danger">Entfernen</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="sidebar">
                    <h3>Zahlungsinformationen</h3>
                    <h3>Zwischensumme: <?php echo htmlspecialchars($totalPrice); ?> €</h3>
                    <h3>Versandkosten: <?php echo htmlspecialchars($shippingCost); ?> €</h3>
                    <h3>Gesamt: <?php echo htmlspecialchars($totalPriceWithShipping); ?> €</h3>
                    <div class="mt-4">
                        <a href="checkout.php" class="btn btn-success">Zur Kasse</a>
                        <a href="cart.php?clear=true" class="btn btn-warning">Warenkorb löschen</a>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<footer>
    <div class="footer-top">
        <div class="footer-container">
            <div class="footer-section">
                <h4>Über uns</h4>
                <ul>
                    <li><a href="../about.php">Unsere Geschichte</a></li>
                    <li><a href="../careers.php">Karriere</a></li>
                    <li><a href="../privacy.php">Datenschutz</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Service</h4>
                <ul>
                    <li><a href="../faq.php">Häufige Fragen</a></li>
                    <li><a href="../returns.php">Rückgaben</a></li>
                    <li><a href="../shipping.php">Versand</a></li>
                </ul>
            </div>
            <div class="newsletter">
                <h3>Newsletter</h3>
                <form action="../subscribe.php" method="post">
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="Ihre E-Mail-Adresse" name="email" required>
                        <button class="btn btn-light" type="submit">Abonnieren</button>
                    </div>
                </form>
            </div>
            <div class="footer-section">
                <h4>Kontakt</h4>
                <ul>
                    <li><a href="../contact.php">Kontaktformular</a></li>
                    <li><a href="../support.php">Support</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Folgen Sie uns</h4>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; 2024 Meine Website. Alle Rechte vorbehalten.</p>
    </div>
</footer>

<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/5.2.0/js/bootstrap.min.js"></script>
</body>
</html>

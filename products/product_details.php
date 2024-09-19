<?php
session_start();
include('../includes/db.php');

// Prüfen, ob die Datenbankverbindung existiert
if (!isset($conn)) {
    die("Datenbankverbindung fehlgeschlagen.");
}

// Prüfen, ob der Benutzer angemeldet ist
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && $_SESSION['user_role'] === 'admin';

// Produkt-ID aus der URL abfragen und validieren
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($product_id <= 0) {
    echo "Ungültige Produkt-ID.";
    exit;
}

// Produkt aus der Datenbank abrufen
$stmt = $conn->prepare("SELECT name, description, price, images FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    echo "Produkt nicht gefunden.";
    exit;
}

?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }
        header {
            background-color: #343a40;
            color: #ffffff;
            padding: 15px 0;
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
        .product-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }
        .product-details {
            margin-top: 20px;
        }
        .btn-cart {
            margin-top: 10px;
        }
        .footer-info {
            background-color: #ffffff;
            color: #343a40;
            padding: 20px 0;
            text-align: center;
            border-top: 1px solid #e2e6ea;
        }
        .footer-info .icon {
            display: inline-block;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #343a40;
            color: #ffffff;
            line-height: 40px;
            text-align: center;
            margin: 0 10px;
        }
        .related-products {
            margin-top: 40px;
        }
        footer {
            background-color: #343a40;
            color: #ffffff;
            padding: 10px 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <header class="bg-dark text-white py-3">
        <div class="container">
            <h1 class="h3">Produktdetails</h1>
            <a href="../index.php" class="btn btn-light"><i class="fas fa-home"></i> Zurück zur Startseite</a>
            <?php if ($isLoggedIn): ?>
                <a href="../logout.php" class="btn btn-light"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="container mt-5">
        <div class="row">
            <div class="col-md-6">
                <img src="../assets/images/<?php echo htmlspecialchars($product['images']); ?>" class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
            </div>
            <div class="col-md-6">
                <div class="product-details">
                    <h2><?php echo htmlspecialchars($product['name']); ?></h2>
                    <p class="lead"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    <p class="h4"><strong>Preis: €<?php echo number_format($product['price'], 2); ?></strong></p>
                    <?php if ($isLoggedIn): ?>
                        <a href="cart.php?add=<?php echo $product_id; ?>" class="btn btn-primary btn-cart"><i class="fas fa-shopping-cart"></i> In den Warenkorb</a>
                        <a href="checkout.php?product=<?php echo $product_id; ?>" class="btn btn-success btn-cart"><i class="fas fa-credit-card"></i> Sofort kaufen</a>
                    <?php else: ?>
                        <a href="../auth/login.php" class="btn btn-primary btn-cart"><i class="fas fa-sign-in-alt"></i> Anmelden, um zu kaufen</a>
                    <?php endif; ?>

                </div>
                <div class="footer-info mt-4">
                    <p><i class="fas fa-truck icon"></i> Schnelles Liefern</p>
                    <p><i class="fas fa-lock icon"></i> Sicheres Bezahlen</p>
                    <p><i class="fas fa-shopping-cart icon"></i> Warenkorb</p>
                </div>
            </div>
        </div>

        <div class="related-products">
            <h5>Das könnte dir auch gefallen:</h5>
            <div class="row">
                <?php
                // Verwandte Produkte abrufen
                $related_stmt = $conn->prepare("SELECT id, name, price, images FROM products WHERE id != ? LIMIT 4");
                $related_stmt->bind_param("i", $product_id);
                $related_stmt->execute();
                $related_products = $related_stmt->get_result();

                while ($related_product = $related_products->fetch_assoc()):
                ?>
                    <div class="col-md-3">
                        <div class="card">
                            <img src="../assets/images/<?php echo htmlspecialchars($related_product['images']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($related_product['name']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($related_product['name']); ?></h5>
                                <p class="card-text">€<?php echo number_format($related_product['price'], 2); ?></p>
                                <a href="product.php?id=<?php echo $related_product['id']; ?>" class="btn btn-primary">Mehr erfahren</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
                <?php $related_stmt->close(); ?>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 Online Shop. Alle Rechte vorbehalten.</p>
    </footer>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

<?php
session_start();
include('includes/db.php');

if (!isset($conn)) {
    die("Datenbankverbindung fehlgeschlagen.");
}



// Warenkorb-Anzahl für angemeldeten Benutzer laden
$cartCount = 0;
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $sql = "SELECT SUM(quantity) AS total_quantity FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $cartCount = $row['total_quantity'] ?? 0;
}

// Suchanfrage verarbeiten
$searchQuery = '';
if (isset($_GET['search'])) {
    $searchQuery = $_GET['search'];
    $searchQuery = "%$searchQuery%";
    $sql = "SELECT * FROM products WHERE name LIKE ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('s', $searchQuery);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        die("Fehler bei der Abfrage.");
    }
} else {
    $sql = "SELECT * FROM products";
    $result = $conn->query($sql);
    if (!$result) {
        die("Fehler bei der Abfrage.");
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Online Shop</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.2.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="./assets/css/style.css?v=1.0">
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
            <a href="index.php"><img src="assets/images/logo.png" alt="Logo"></a>
        </div>
        <div class="search-container">
            <form action="index.php" method="get">
                <input type="text" name="search" placeholder="Suche nach Produkten" value="<?php echo htmlspecialchars($searchQuery); ?>">
            </form>
        </div>
        <div class="nav-icons">
                <!-- Warenkorb-Button -->
                <a href="products/cart.php?user_id=<?php echo $userId; ?>" class="icon">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count"><?php echo $cartCount; ?></span>
                </a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="user/profile.php" class="icon"><i class="fas fa-user"></i></a>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <a href="admin/dashboard.php" class="icon"><i class="fas fa-cogs"></i></a>
                <?php endif; ?>
                <a href="auth/logout.php" class="icon"><i class="fas fa-sign-out-alt"></i></a>
            <?php else: ?>
                <a href="auth/login.php" class="icon"><i class="fas fa-user-plus"></i></a>
            <?php endif; ?>
        </div>
    </div>
    <hr class="divider">
    <nav class="main-nav">
        <ul>
            <li><a href="index.php">Startseite</a></li>
            <li><a href="categories.php">Kategorien</a></li>
            <li><a href="products.php">Produkte</a></li>
            <li><a href="contact.php">Kontakt</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="user/profile.php">Profil</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<main>
    <div class="hero">
        <h1>Willkommen bei unserem modernen Online-Shop</h1>
        <p>Finden Sie die besten Produkte zum besten Preis</p>
        <a href="products.php" class="shop-now-button">Jetzt einkaufen</a>
    </div>

    <section class="product-categories">
        <h2>Unsere Kategorien</h2>
        <div class="category-grid">
            <div class="category-item">
                <img src="category1.jpg" alt="Kategorie 1">
                <h3>Elektronik</h3>
            </div>
            <div class="category-item">
                <img src="category2.jpg" alt="Kategorie 2">
                <h3>Bekleidung</h3>
            </div>
            <div class="category-item">
                <img src="category3.jpg" alt="Kategorie 3">
                <h3>Haus & Garten</h3>
            </div>
            <div class="category-item">
                <img src="category4.jpg" alt="Kategorie 4">
                <h3>Sport</h3>
            </div>
        </div>
    </section>

    <div class="featured-products">
    <h2>Unsere Produkte</h2>
    <div class="product-list">
        <?php while ($product = $result->fetch_assoc()): ?>
            <div class="product-card">
                <img src="assets/images/<?php echo htmlspecialchars($product['images']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                <p class="price"><?php echo htmlspecialchars($product['price']); ?> €</p>
                <button class="btn btn-primary add-to-cart" data-product-id="<?php echo htmlspecialchars($product['id']); ?>">In den Warenkorb</button>
                <a href="products/product_details.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="btn btn-secondary view-details">Details</a>
            </div>
        <?php endwhile; ?>
    </div>
</div>

</main>

<footer>
    <div class="footer-top">
        <div class="footer-container">
            <div class="footer-section">
                <h4>Über uns</h4>
                <ul>
                    <li><a href="about.php">Unsere Geschichte</a></li>
                    <li><a href="careers.php">Karriere</a></li>
                    <li><a href="privacy.php">Datenschutz</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Service</h4>
                <ul>
                    <li><a href="faq.php">Häufige Fragen</a></li>
                    <li><a href="returns.php">Rückgaben</a></li>
                    <li><a href="shipping.php">Versand</a></li>
                </ul>
            </div>
            <div class="newsletter">
                <h3>Newsletter</h3>
                <form action="subscribe.php" method="post">
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="Ihre E-Mail-Adresse" name="email" required>
                        <button class="btn btn-light" type="submit">Abonnieren</button>
                    </div>
                </form>
            </div>
            <div class="footer-section">
                <h4>Kontakt</h4>
                <ul>
                    <li><a href="contact.php">Kontaktformular</a></li>
                    <li><a href="support.php">Support</a></li>
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
    <hr class="divider">
    <div class="footer-info">
        <ul>
            <li><a href="terms.php">AGB</a></li>
            <li><a href="privacy.php">Datenschutz</a></li>
        </ul>
    </div>
    <div class="footer-payments">
        <h4>Zahlungsmethoden</h4>
        <i class="fa fa-cc-amex"></i>
        <i class="fa fa-cc-diners-club"></i>
        <i class="fa fa-cc-discover"></i>
        <i class="fa fa-cc-jcb"></i>
        <i class="fa fa-cc-mastercard"></i>
        <i class="fa fa-cc-paypal"></i>
        <i class="fa fa-cc-visa"></i>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/5.2.0/js/bootstrap.bundle.min.js"></script>
<script src="./assets/js/script.js"></script>
</body>
</html>

<script>
    document.addEventListener('DOMContentLoaded', () => {
    const userId = <?php echo json_encode($_SESSION['user_id']); ?>;

    const addToCartButtons = document.querySelectorAll('.add-to-cart');

    addToCartButtons.forEach(button => {
        button.addEventListener('click', () => {
            const productId = button.getAttribute('data-product-id');

            if (!userId || !productId) {
                console.error('Benutzer-ID oder Produkt-ID fehlt.');
                return;
            }

            fetch('products/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `product_id=${encodeURIComponent(productId)}&user_id=${encodeURIComponent(userId)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.cart_count !== undefined) {
                    document.querySelector('.cart-count').textContent = data.cart_count;
                } else {
                    console.error(data.error);
                }
            })
            .catch(error => console.error('Fehler:', error));
        });
    });
});

</script>
<?php
session_start();
include('../includes/db.php'); // Pfad zum db.php im includes-Verzeichnis

if (!isset($conn)) {
    die("Datenbankverbindung fehlgeschlagen.");
}

// Prüfen, ob der Benutzer angemeldet ist und Admin oder Moderator ist
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && $_SESSION['user_role'] === 'admin';
$isModerator = $isLoggedIn && $_SESSION['user_role'] === 'moderator';

if (!$isAdmin && !$isModerator) {
    header('Location: ../');
    exit();
}

// Suchanfrage verarbeiten
$searchQuery = '';
if (isset($_GET['search'])) {
    $searchQuery = $_GET['search'];
    $searchQuery = "%$searchQuery%";
}

// Benutzerverwaltung
$users = [];
if ($isAdmin) {
    $sql = "SELECT * FROM users";
    if (!empty($searchQuery)) {
        $sql .= " WHERE username LIKE '$searchQuery' OR email LIKE '$searchQuery'";
    }
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $users = $result->fetch_all(MYSQLI_ASSOC);
    }
}

// Online-/Offline-/Abwesend-Status
$totalOnline = 0;
$totalOffline = 0;
$totalAway = 0;
$totalBanned = 0;

foreach ($users as $user) {
    switch ($user['status']) {
        case 'online':
            $totalOnline++;
            break;
        case 'away':
            $totalAway++;
            break;
        case 'offline':
        default:
            $totalOffline++;
            break;
    }
    // Prüfen, ob der Benutzer gesperrt ist
    if ($user['ban_until'] && $user['ban_until'] > time()) {
        $totalBanned++;
    }
}

// Bestellungen einsehen
$orders = [];
if ($isAdmin || $isModerator) {
    $sql = "SELECT * FROM orders";
    if (!empty($searchQuery)) {
        $sql .= " WHERE id LIKE '$searchQuery'";
    }
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $orders = $result->fetch_all(MYSQLI_ASSOC);
    }
}

// Produkte verwalten
$products = [];
if ($isAdmin) {
    $sql = "SELECT * FROM products";
    if (!empty($searchQuery)) {
        $sql .= " WHERE name LIKE '$searchQuery'";
    }
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $products = $result->fetch_all(MYSQLI_ASSOC);
    }
}

// Statistiken
$statistics = [];
if ($isAdmin) {
    $sql = "SELECT COUNT(*) AS total_users FROM users";
    $result = $conn->query($sql);
    $total_users = $result->fetch_assoc()['total_users'];

    $sql = "SELECT COUNT(*) AS total_orders FROM orders";
    $result = $conn->query($sql);
    $total_orders = $result->fetch_assoc()['total_orders'];

    $sql = "SELECT COUNT(*) AS total_products FROM products";
    $result = $conn->query($sql);
    $total_products = $result->fetch_assoc()['total_products'];

    $statistics = [
        'total_users' => $total_users,
        'total_orders' => $total_orders,
        'total_products' => $total_products,
        'total_banned_users' => $totalBanned,
        'total_online_users' => $totalOnline,
        'total_away_users' => $totalAway,
        'total_offline_users' => $totalOffline,
    ];
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin-Panel</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=1.0">
</head>
<body>
    <div class="sidebar">
        <h2>Admin-Panel</h2>
        <a href="#" id="dashboard-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <?php if ($isAdmin): ?>
            <a href="#users" id="users-link"><i class="fas fa-users"></i> Benutzerverwaltung</a>
        <?php endif; ?>
        <a href="#orders" id="orders-link"><i class="fas fa-box"></i> Bestellungen</a>
        <?php if ($isAdmin): ?>
            <a href="#products" id="products-link"><i class="fas fa-box"></i> Produkte</a>
        <?php endif; ?>
        <a href="../"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="content">
        <!-- Dashboard Inhalt -->
        <div id="dashboard" class="content-section">
            <h1>Willkommen im Admin-Panel</h1>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow-sm rounded">
                        <div class="card-body">
                            <h5 class="card-title">Benutzer</h5>
                            <p class="card-text">Statistik der Benutzer.</p>
                            <h3><?php echo $statistics['total_users']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow-sm rounded">
                        <div class="card-body">
                            <h5 class="card-title">Bestellungen</h5>
                            <p class="card-text">Statistik der Bestellungen.</p>
                            <h3><?php echo $statistics['total_orders']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow-sm rounded">
                        <div class="card-body">
                            <h5 class="card-title">Produkte</h5>
                            <p class="card-text">Statistik der Produkte.</p>
                            <h3><?php echo $statistics['total_products']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow-sm rounded">
                        <div class="card-body">
                            <h5 class="card-title">Gebannte Benutzer</h5>
                            <p class="card-text">Statistik der gesperrten Benutzer.</p>
                            <h3><?php echo $statistics['total_banned_users']; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card border-0 shadow-sm rounded">
                        <div class="card-body">
                            <h5 class="card-title">Online-Status</h5>
                            <p class="card-text">Statistik der Benutzerstatus.</p>
                            <h3>Online: <?php echo $statistics['total_online_users']; ?></h3>
                            <h3>Abwesend: <?php echo $statistics['total_away_users']; ?></h3>
                            <h3>Offline: <?php echo $statistics['total_offline_users']; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<!-- Benutzerverwaltung -->
<div id="users" class="content-section" style="display:none;">
    <h2>Benutzerverwaltung</h2>
    <form method="GET" action="">
        <input type="text" name="search" placeholder="Benutzer suchen" class="form-control mb-3">
    </form>
    <?php if ($isAdmin): ?>
        <div class="scrollable-table">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Benutzername</th>
                        <th>E-Mail</th>
                        <th>Ban Status</th>
                        <th>Online Status</th>
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
                            <td>
                                <?php if ($user['ban_until'] && $user['ban_until'] > time()): ?>
                                    <span class="status-banned">Gebannt bis <?php echo date('d.m.Y H:i:s', $user['ban_until']); ?></span>
                                <?php else: ?>  
                                    <span class="status-active">Aktiv</span>
                                <?php endif; ?>
                            </td>
                            <style>
    .status-box {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 15px;
        color: #fff;
        font-weight: bold;
        text-align: center;
    }
    .status-online {
        background-color: #28a745; /* Grün für online */
    }
    .status-offline {
        background-color: #dc3545; /* Rot für offline */
    }
    .status-unknown {
        background-color: #6c757d; /* Grau für unbekannt */
    }
</style>

<td>
    <?php if ($user['status'] === 'online'): ?>
        <span class="status-box status-online">Online</span>
    <?php elseif ($user['status'] === 'offline'): ?>
        <span class="status-box status-offline">Offline</span>
    <?php else: ?>
        <span class="status-box status-unknown">Unbekannt</span>
    <?php endif; ?>
</td>

                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                            <td>
                                <?php if ($user['ban_until'] && $user['ban_until'] > time()): ?>
                                    <button onclick="openBanInfoModal(<?php echo htmlspecialchars($user['id']); ?>)" class="btn btn-info btn-sm">
                                        <i class="fas fa-info-circle"></i> Ban-Info
                                    </button>
                                <?php endif; ?>
                                <!-- Bearbeitungs-Button -->
                                <a href="edit_user.php?id=<?php echo htmlspecialchars($user['id']); ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Bearbeiten
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
function openBanInfoModal(userId) {
    // Hier sollte der Code zum Öffnen des Modals stehen, das die Ban-Details anzeigt.
    alert("Ban-Info für Benutzer ID: " + userId);
}
</script>


<!-- Ban-Info Modal -->
<div id="banInfoModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeBanInfoModal()">&times;</span>
        <h2>Ban-Informationen</h2>
        <div id="banInfoContent">
            <!-- Die Ban-Informationen werden hier eingefügt -->
        </div>
    </div>
</div>


        <!-- Bestellungen -->
        <div id="orders" class="content-section" style="display:none;">
            <h2>Bestellungen</h2>
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Bestellungen suchen" class="form-control mb-3">
            </form>
            <div class="scrollable-table">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Produkt</th>
                            <th>Anzahl</th>
                            <th>Preis</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['id']); ?></td>
                                <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['quantity']); ?></td>
                                <td><?php echo htmlspecialchars($order['price']); ?> €</td>
                                <td><?php echo htmlspecialchars($order['status']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Produkte -->
        <div id="products" class="content-section" style="display:none;">
            <h2>Produkte</h2>
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Produkte suchen" class="form-control mb-3">
               <!-- HTML !-->
                <a href="add_product.php" class="btn btn-primary"> <i class="fas fa-box"></i> Hinzufügen</a>
            </form>
            <div class="scrollable-table">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Produktname</th>
                            <th>Preis</th>
                            <th>Verfügbarkeit</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['id']); ?></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['price']); ?> €</td>
                                <td><?php echo htmlspecialchars($product['stock']); ?></td>
                                <td>
                                    <a href="admin/edit_product.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i> Bearbeiten</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('dashboard-link').addEventListener('click', function () {
            showSection('dashboard');
        });
        document.getElementById('users-link').addEventListener('click', function () {
            showSection('users');
        });
        document.getElementById('orders-link').addEventListener('click', function () {
            showSection('orders');
        });
        document.getElementById('products-link').addEventListener('click', function () {
            showSection('products');
        });

        function showSection(id) {
            document.querySelectorAll('.content-section').forEach(section => {
                section.style.display = 'none';
            });
            document.getElementById(id).style.display = 'block';
        }


        function openBanInfoModal(userId) {
        // AJAX-Anfrage, um Ban-Informationen zu laden
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'get_ban_info.php?id=' + userId, true);
        xhr.onload = function () {
            if (xhr.status === 200) {
                document.getElementById('banInfoContent').innerHTML = xhr.responseText;
                document.getElementById('banInfoModal').style.display = 'block';
            } else {
                alert('Fehler beim Laden der Ban-Informationen.');
            }
        };
        xhr.send();
    }

    function closeBanInfoModal() {
        document.getElementById('banInfoModal').style.display = 'none';
    }
    </script>
</body>
</html>

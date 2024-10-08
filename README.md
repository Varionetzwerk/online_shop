# Online Shop Ordnerstruktur

![GitHub](https://img.shields.io/badge/Project-GitHub-blue)
![PHP](https://img.shields.io/badge/Language-PHP-4F5B93)
![Database](https://img.shields.io/badge/Database-MariaDB-0082C9)
![Status](https://img.shields.io/badge/Status-Alpha-orange.png)


## 🚀 Projektübersicht

Dieses Projekt erstellt die grundlegende Ordner- und Dateistruktur für einen Online-Shop. Es befindet sich derzeit in der **Alpha-Phase**, und es werden kontinuierlich Funktionen hinzugefügt und optimiert.

## 📂 Ordnerstruktur

Die folgende Struktur wird erstellt:

```bash
online_shop/
│
├── admin/                     # Admin-Dashboard Dateien
│   ├── dashboard.php           # Admin-Dashboard
│   ├── add_product.php         # Produkt hinzufügen
│   ├── edit_user.php           # Benutzer bearbeiten
│   ├── get_ban_info.php        # Bann-Informationen abrufen
│   ├── orders.php              # Bestellübersicht
│   └── users.php               # Benutzerübersicht
│
├── assets/                     # Statische Dateien wie CSS, JS, Bilder
│   ├── css/
│   │   ├── style.css           # Allgemeine CSS-Datei
│   │   ├── dashboard.css       # CSS-Datei für das Admin-Dashboard
│   │   ├── login.css           # CSS-Datei für Login-Seite
│   │   └── edit_user.css       # CSS-Datei für Benutzer bearbeiten
│   ├── js/
│   │   └── script.js           # JavaScript Datei
│   └── images/                 # Bilder
│
├── includes/                   # PHP-Dateien für Funktionen und Datenbank
│   └── db.php                  # Datenbankverbindung
│
├── user/                       # Benutzerspezifische Seiten
│   ├── profile.php             # Benutzerprofilseite
│   ├── cron_update_status.php  # Cronjob zum Status-Update
│   └── update_activity.php     # Benutzeraktivität aktualisieren
│
├── products/                   # Produktseiten
│   ├── cart.php                # Warenkorb
│   └── product_details.php     # Produktdetails
│
├── auth/                       # Authentifizierungsseiten
│   ├── login.php               # Login-Seite
│   ├── forgot_password.php     # Seite zum Zurücksetzen des Passworts
│   ├── reset_password.php      # Passwort-Reset-Seite
│   └── logout.php              # Logout-Seite
│
├── checkout/                   # Checkout-Seite
│   └── checkout.php            # Checkout-Übersicht
│
├── config.php                  # Hauptkonfigurationsdatei
├── index.php                   # Startseite
└── .htaccess                   # URL-Rewriting Einstellungen

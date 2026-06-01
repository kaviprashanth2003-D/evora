<?php
// Global database settings
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'evoraa_db');

/**
 * Returns a secure PDO database connection interface.
 * Implements self-healing schema checks to guarantee the database 
 * and required structures exist on first request.
 */
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo !== null) {
        return $pdo;
    }
    
    try {
        // Step 1: Connect to server without database to check/create it
        $dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $temp_pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        $temp_pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $temp_pdo = null;
        
        // Step 2: Connect to specific database
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        // Step 3: Self-heal tables compiler
        initializeDatabaseSchema($pdo);
        
        return $pdo;
    } catch (PDOException $e) {
        // Safe logging in production, display clean error
        error_log("Database connection failure: " . $e->getMessage());
        die("System error: A secure database connection could not be established. Please try again later.");
    }
}

/**
 * Creates tables if they do not exist and seeds initial collections metadata.
 */
function initializeDatabaseSchema($pdo) {
    // 1. Admin Users Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `admin_users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(100) NOT NULL UNIQUE,
        `password_hash` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // 2. Products Inventory Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `products` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `product_code` VARCHAR(50) NOT NULL UNIQUE,
        `name` VARCHAR(150) NOT NULL,
        `category` VARCHAR(50) NOT NULL,
        `description` TEXT NOT NULL,
        `image1` VARCHAR(255) NOT NULL,
        `image2` VARCHAR(255) NULL,
        `image3` VARCHAR(255) NULL,
        `image4` VARCHAR(255) NULL,
        `original_price` DECIMAL(10,2) NOT NULL,
        `discount_price` DECIMAL(10,2) NOT NULL,
        `discount_active` TINYINT(1) DEFAULT 0,
        `offer_badge` VARCHAR(50) NULL,
        `stock_xs` INT DEFAULT 0,
        `stock_s` INT DEFAULT 0,
        `stock_m` INT DEFAULT 0,
        `stock_l` INT DEFAULT 0,
        `stock_xl` INT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // 3. Announcements Marquee Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `announcements` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `text` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // 4. Marketing Banners Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `banners` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `image_path` VARCHAR(255) NOT NULL,
        `link_path` VARCHAR(255) NOT NULL,
        `title` VARCHAR(100) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // 6. Hero Images Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `hero_images` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `filename` VARCHAR(255) NOT NULL,
        `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `is_active` TINYINT(1) DEFAULT 1
    ) ENGINE=InnoDB;");

    // 5. Orders Invoice Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `orders` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `order_hash` VARCHAR(64) NOT NULL UNIQUE,
        `customer_name` VARCHAR(100) NOT NULL,
        `customer_email` VARCHAR(100) NOT NULL,
        `customer_phone` VARCHAR(20) NOT NULL,
        `customer_address` TEXT NOT NULL,
        `city` VARCHAR(50) NOT NULL,
        `zip` VARCHAR(20) NULL,
        `delivery_tier` VARCHAR(50) NOT NULL,
        `shipping_fee` DECIMAL(10,2) NOT NULL,
        `payment_method` VARCHAR(50) NOT NULL,
        `receipt_path` VARCHAR(255) NULL,
        `subtotal` DECIMAL(10,2) NOT NULL,
        `discount_amount` DECIMAL(10,2) NOT NULL,
        `total` DECIMAL(10,2) NOT NULL,
        `status` VARCHAR(20) DEFAULT 'Pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // 6. Order Items Sub-Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `order_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `order_id` INT NOT NULL,
        `product_id` INT NOT NULL,
        `product_name` VARCHAR(150) NOT NULL,
        `size` VARCHAR(10) NOT NULL,
        `qty` INT NOT NULL,
        `price` DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB;");

    // 7. Customer Accounts Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `customers` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(100) NOT NULL UNIQUE,
        `password_hash` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // 8. Customer Feedback / Reviews Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `feedback` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `customer_name` VARCHAR(100) NOT NULL,
        `rating` INT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
        `comment` TEXT NOT NULL,
        `approved` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // Seed sample feedback/testimonials if empty
    $count = $pdo->query("SELECT COUNT(*) FROM `feedback`")->fetchColumn();
    if ($count == 0) {
        $reviews = [
            ["Aisha M.", 5, "Absolutely obsessed with the Aura Linen Dress. The quality is unmatched and delivery was so fast. EVORAA is my new go-to label!"],
            ["Dilini R.", 5, "The silk bias cut skirt is everything I dreamed of. Luxury feel at a really great price point."],
            ["Priya S.", 4, "Love the packaging and the attention to detail. The knit set fits perfectly. Will definitely order again."],
        ];
        $stmt = $pdo->prepare("INSERT INTO `feedback` (`customer_name`, `rating`, `comment`) VALUES (?, ?, ?)");
        foreach ($reviews as $r) {
            $stmt->execute($r);
        }
    }

    // --- SEED SEED DATA IF EMPTY ---
    
    // Seed default admin user if empty
    $count = $pdo->query("SELECT COUNT(*) FROM `admin_users`")->fetchColumn();
    if ($count == 0) {
        $hash = password_hash('admin1234', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO `admin_users` (`name`, `email`, `password_hash`) VALUES (?, ?, ?)");
        $stmt->execute(['Administrator', 'admin@gmail.com', $hash]);
    }

    // 1. Stats Aggregation
    $totalOrders = $pdo->query("SELECT COUNT(*) FROM `orders`")->fetchColumn();
    $pendingOrders = $pdo->query("SELECT COUNT(*) FROM `orders` WHERE `status` = 'Pending'")->fetchColumn();
    $paidOrders = $pdo->query("SELECT COUNT(*) FROM `orders` WHERE `status` IN ('Receipt Uploaded', 'Approved', 'Shipped')")->fetchColumn();
    $completedRevenue = $pdo->query("SELECT SUM(total) FROM `orders` WHERE `status` IN ('Approved', 'Shipped')")->fetchColumn() ?: 0.00;

    // 2. Hero Images
    $heroImagesQuery = $pdo->query("SELECT * FROM `hero_images` WHERE `is_active` = 1 ORDER BY `uploaded_at` DESC");
    $heroImages = $heroImagesQuery->fetchAll();
    
    // Seed default product drops if empty
    $count = $pdo->query("SELECT COUNT(*) FROM `products`")->fetchColumn();
    if ($count == 0) {
        $products = [
            [
                "prod-001", "Aura Linen Halter Maxi Dress", "MAXI DRESSES", 
                "An elegant, flowing maxi dress crafted from premium organic Sri Lankan linen. Features a sophisticated halter neckline, low open back, and dual side slits for liquid-like movement. Perfect for sunset soirées.",
                "https://images.unsplash.com/photo-1595777457583-95e059d581b8?q=80&w=600&auto=format&fit=crop",
                "https://images.unsplash.com/photo-1539008835657-9e8e9680fe0a?q=80&w=600&auto=format&fit=crop",
                12500, 10625, 1, "15% OFF", 5, 8, 12, 6, 4
            ],
            [
                "prod-002", "Luxe Crepe Pleated Pants", "PANTS", 
                "Tailored to high-fashion perfection. These high-waisted crepe pants feature structural front pleats, a wide-leg silhouette, and concealed pocket enclosures. Offers both structure and luxury drape.",
                "https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?q=80&w=600&auto=format&fit=crop",
                "https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?q=80&w=600&auto=format&fit=crop",
                8900, 8900, 0, "NEW DROP", 4, 10, 15, 8, 2
            ],
            [
                "prod-003", "Silk Satin Bias Cut Skirt", "SKIRTS", 
                "Cut on the bias to drape effortlessly over curves. Crafted from liquid-finish silk satin with a delicate elasticated waistband for maximum comfort. Features a soft cream palette that matches any premium capsule closet.",
                "https://images.unsplash.com/photo-1583391733956-3750e0ff4e8b?q=80&w=600&auto=format&fit=crop",
                "https://images.unsplash.com/photo-1609357518652-6cf0416f0cbe?q=80&w=600&auto=format&fit=crop",
                9500, 7500, 1, "SALE", 3, 6, 8, 5, 3
            ],
            [
                "prod-004", "Ethereal Knit Wrap Top", "TOPS", 
                "Meticulously spun from fine gauge cotton-silk yarn. Designed with elongated wraps that cinch the waist and a soft plunge ribbed neckline. Lightweight, breathable, and highly premium.",
                "https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?q=80&w=600&auto=format&fit=crop",
                "https://images.unsplash.com/photo-1509631179647-0177331693ae?q=80&w=600&auto=format&fit=crop",
                6200, 6200, 0, "", 8, 12, 12, 10, 5
            ],
            [
                "prod-005", "Rosmead Linen Classic Shirt", "SHIRTS", 
                "The ultimate relaxed tailored shirt. Features structural horn buttons, a classic drop-shoulder style, and oversized utility breast pockets. Extremely versatile, double-hemmed for longevity.",
                "https://images.unsplash.com/photo-1607345366928-199ea26cfe3e?q=80&w=600&auto=format&fit=crop",
                "https://images.unsplash.com/photo-1548624149-f7b3be6894fd?q=80&w=600&auto=format&fit=crop",
                7900, 7900, 0, "BUY 1 GET 1", 6, 10, 15, 12, 6
            ],
            [
                "prod-006", "Structured Denim Trench", "DENIM", 
                "A showstopping minimalist trench jacket. Features mid-weight premium raw Japanese denim, a double-breasted button panel, and a coordinated wrap belt. Tailored to wow at first glance.",
                "https://images.unsplash.com/photo-1611312449412-6cefac5dc3e4?q=80&w=600&auto=format&fit=crop",
                "https://images.unsplash.com/photo-1541099649105-f69ad21f3246?q=80&w=600&auto=format&fit=crop",
                18500, 18500, 0, "CLASSIC", 2, 5, 6, 4, 2
            ],
            [
                "prod-007", "Atelier Ribbed Knit Set", "SETS", 
                "A coordinated two-piece top and skirt set crafted from a heavy ribbed viscose blend. Fits seamlessly, offering a luxurious weight that retains its shape and moves elegantly.",
                "https://images.unsplash.com/photo-1496747611176-843222e1e57c?q=80&w=600&auto=format&fit=crop",
                "https://images.unsplash.com/photo-1529139574466-a303027c1d8b?q=80&w=600&auto=format&fit=crop",
                16900, 13520, 1, "20% OFF", 3, 8, 10, 6, 4
            ]
        ];

        $stmt = $pdo->prepare("INSERT INTO `products` 
            (`product_code`, `name`, `category`, `description`, `image1`, `image2`, `original_price`, `discount_price`, `discount_active`, `offer_badge`, `stock_xs`, `stock_s`, `stock_m`, `stock_l`, `stock_xl`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
        foreach ($products as $p) {
            $stmt->execute($p);
        }
    }

    // Seed default marquee announcements if empty
    $count = $pdo->query("SELECT COUNT(*) FROM `announcements`")->fetchColumn();
    if ($count == 0) {
        $announcements = [
            "FREE ISLANDWIDE DELIVERY ON ORDERS OVER 15,000 LKR",
            "NEW ARRIVALS: EXPLORE THE DROP OF CURATED MINIMALISM",
            "SUBSCRIBE TODAY FOR AN IMMEDIATE 10% COUPON CODE"
        ];
        
        $stmt = $pdo->prepare("INSERT INTO `announcements` (`text`) VALUES (?)");
        foreach ($announcements as $text) {
            $stmt->execute([$text]);
        }
    }

    // Seed default banner slideshows if empty
    $count = $pdo->query("SELECT COUNT(*) FROM `banners`")->fetchColumn();
    if ($count == 0) {
        $banners = [
            [
                "https://images.unsplash.com/photo-1490481651871-ab68de25d43d?q=80&w=1600&auto=format&fit=crop",
                "shop.php",
                "EXPLORE THE DROP"
            ]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO `banners` (`image_path`, `link_path`, `title`) VALUES (?, ?, ?)");
        foreach ($banners as $b) {
            $stmt->execute($b);
        }
    }

    // Self-heal: add image3/image4 columns for existing installations
    try {
        $pdo->exec("ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `image3` VARCHAR(255) NULL AFTER `image2`");
        $pdo->exec("ALTER TABLE `products` ADD COLUMN IF NOT EXISTS `image4` VARCHAR(255) NULL AFTER `image3`");
    } catch (Exception $e) {
        // Columns may already exist; ignore gracefully
    }
}
?>

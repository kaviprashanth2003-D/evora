<?php
require_once __DIR__ . '/auth.php';

// Enforce auth check. If unauthorized, will redirect to login.php.
requireAdminAuth();

$pdo = getDBConnection();
$message = '';
$error = '';

/**
 * Helper to process file uploads safely.
 * Saves image into uploads/products/{productCode}/ folder.
 * $productCode = admin-defined product code (e.g. prod-008)
 * $slot        = 'image1', 'image2', 'image3', or 'image4'
 */
function uploadProductImage($fieldName, $productCode = 'temp', $slot = 'image') {
    if (isset($_FILES[$fieldName]) && $_FILES[$fieldName]['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES[$fieldName];
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($fileExt, $allowedExts)) {
            throw new Exception("Only JPG, PNG, and WEBP image uploads are allowed.");
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception("Uploaded file exceeds 5MB size limit.");
        }

        // Sanitize product code for safe folder name (e.g. "prod-008" stays as-is)
        $safeCode = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $productCode);

        $destDir = __DIR__ . '/../uploads/products/' . $safeCode . '/';
        if (!is_dir($destDir)) {
            mkdir($destDir, 0777, true);
        }

        // Clean filename: image1.jpg or image2.jpg
        $newFilename = $slot . '.' . $fileExt;
        $destPath = $destDir . $newFilename;

        // Remove old file if exists
        if (file_exists($destPath)) {
            unlink($destPath);
        }

        if (move_uploaded_file($file['tmp_name'], $destPath)) {
            return 'uploads/products/' . $safeCode . '/' . $newFilename;
        }
    }
    return null;
}

// --- CONTROLLER POST ACTION DISPATCHER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'logout') {
            logoutAdmin();
            header("Location: login.php");
            exit();
        }
        
        // 1. Update Order Status
        if ($action === 'update_order_status') {
            $hash = trim($_POST['order_hash'] ?? '');
            $status = trim($_POST['status'] ?? '');
            
            if (empty($hash) || empty($status)) {
                throw new Exception("Order hash and status are required.");
            }
            
            $stmt = $pdo->prepare("UPDATE `orders` SET `status` = ? WHERE `order_hash` = ?");
            $stmt->execute([$status, $hash]);
            $message = "Order status updated successfully.";
        }
        
        // 2. Add Product Drop
        if ($action === 'add_product') {
            $code = trim($_POST['product_code'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $originalPrice = floatval($_POST['original_price'] ?? 0);
            $discountPrice = floatval($_POST['discount_price'] ?? 0);
            $discountActive = isset($_POST['discount_active']) ? 1 : 0;
            $offerBadge = trim($_POST['offer_badge'] ?? '');
            $xs = intval($_POST['stock_xs'] ?? 0);
            $s = intval($_POST['stock_s'] ?? 0);
            $m = intval($_POST['stock_m'] ?? 0);
            $l = intval($_POST['stock_l'] ?? 0);
            $xl = intval($_POST['stock_xl'] ?? 0);
            
            if (empty($code) || empty($name) || empty($category) || empty($description)) {
                throw new Exception("Product code, name, category, and description are required.");
            }
            
            // Insert product first with placeholder images to get the new ID
            $img1 = trim($_POST['image1_url'] ?? '');
            $img2 = trim($_POST['image2_url'] ?? '');
            $img3 = trim($_POST['image3_url'] ?? '');
            $img4 = trim($_POST['image4_url'] ?? '');

            if (empty($img1) && (!isset($_FILES['image1_file']) || $_FILES['image1_file']['error'] !== UPLOAD_ERR_OK)) {
                throw new Exception("Primary product image is required (upload a file or enter an image URL).");
            }

            $stmt = $pdo->prepare("INSERT INTO `products` 
                (`product_code`, `name`, `category`, `description`, `image1`, `image2`, `image3`, `image4`, `original_price`, `discount_price`, `discount_active`, `offer_badge`, `stock_xs`, `stock_s`, `stock_m`, `stock_l`, `stock_xl`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $code, $name, $category, $description, $img1, $img2, $img3, $img4, $originalPrice, $discountPrice, $discountActive, $offerBadge, $xs, $s, $m, $l, $xl
            ]);
            $newProductId = $pdo->lastInsertId();

            // Now upload images into uploads/products/{code}/ folder
            $uploadedImg1 = uploadProductImage('image1_file', $code, 'image1');
            if ($uploadedImg1) $img1 = $uploadedImg1;

            $uploadedImg2 = uploadProductImage('image2_file', $code, 'image2');
            if ($uploadedImg2) $img2 = $uploadedImg2;

            $uploadedImg3 = uploadProductImage('image3_file', $code, 'image3');
            if ($uploadedImg3) $img3 = $uploadedImg3;

            $uploadedImg4 = uploadProductImage('image4_file', $code, 'image4');
            if ($uploadedImg4) $img4 = $uploadedImg4;

            if (empty($img1)) {
                throw new Exception("Primary product image is required.");
            }

            // Update the record with correct image paths
            $pdo->prepare("UPDATE `products` SET `image1` = ?, `image2` = ?, `image3` = ?, `image4` = ? WHERE `id` = ?")
                ->execute([$img1, $img2, $img3, $img4, $newProductId]);

            $message = "Product drop added successfully.";
        }
        
        // 3. Edit Product Drop
        if ($action === 'edit_product') {
            $id = intval($_POST['product_id'] ?? 0);
            $code = trim($_POST['product_code'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $originalPrice = floatval($_POST['original_price'] ?? 0);
            $discountPrice = floatval($_POST['discount_price'] ?? 0);
            $discountActive = isset($_POST['discount_active']) ? 1 : 0;
            $offerBadge = trim($_POST['offer_badge'] ?? '');
            $xs = intval($_POST['stock_xs'] ?? 0);
            $s = intval($_POST['stock_s'] ?? 0);
            $m = intval($_POST['stock_m'] ?? 0);
            $l = intval($_POST['stock_l'] ?? 0);
            $xl = intval($_POST['stock_xl'] ?? 0);
            
            if ($id <= 0 || empty($code) || empty($name) || empty($category) || empty($description)) {
                throw new Exception("Product code, name, category, and description are required.");
            }
            
            // Get existing product images
            $existingStmt = $pdo->prepare("SELECT `image1`, `image2`, `image3`, `image4` FROM `products` WHERE `id` = ?");
            $existingStmt->execute([$id]);
            $existing = $existingStmt->fetch();
            
            // Handle file uploads into uploads/products/{code}/ folder
            $img1 = uploadProductImage('image1_file', $code, 'image1') ?: (trim($_POST['image1_url'] ?? '') ?: ($existing['image1'] ?? ''));
            $img2 = uploadProductImage('image2_file', $code, 'image2') ?: (trim($_POST['image2_url'] ?? '') ?: ($existing['image2'] ?? NULL));
            $img3 = uploadProductImage('image3_file', $code, 'image3') ?: (trim($_POST['image3_url'] ?? '') ?: ($existing['image3'] ?? NULL));
            $img4 = uploadProductImage('image4_file', $code, 'image4') ?: (trim($_POST['image4_url'] ?? '') ?: ($existing['image4'] ?? NULL));
            
            if (empty($img1)) {
                throw new Exception("Primary product image is required.");
            }
            
            $stmt = $pdo->prepare("UPDATE `products` SET 
                `product_code` = ?, `name` = ?, `category` = ?, `description` = ?, `image1` = ?, `image2` = ?, `image3` = ?, `image4` = ?,
                `original_price` = ?, `discount_price` = ?, `discount_active` = ?, `offer_badge` = ?, 
                `stock_xs` = ?, `stock_s` = ?, `stock_m` = ?, `stock_l` = ?, `stock_xl` = ? 
                WHERE `id` = ?");
            $stmt->execute([
                $code, $name, $category, $description, $img1, $img2, $img3, $img4, $originalPrice, $discountPrice, $discountActive, $offerBadge, $xs, $s, $m, $l, $xl, $id
            ]);
            $message = "Product drop updated successfully.";
        }
        
        // 4. Delete Product Drop
        if ($action === 'delete_product') {
            $id = intval($_POST['product_id'] ?? 0);
            if ($id <= 0) {
                throw new Exception("Invalid product selection.");
            }
            
            $stmt = $pdo->prepare("DELETE FROM `products` WHERE `id` = ?");
            $stmt->execute([$id]);
            $message = "Product drop removed successfully.";
        }
        
        // 5. Add Announcement Marquee Text
        if ($action === 'add_announcement') {
            $text = trim($_POST['text'] ?? '');
            if (empty($text)) {
                throw new Exception("Announcement text cannot be empty.");
            }
            
            $stmt = $pdo->prepare("INSERT INTO `announcements` (`text`) VALUES (?)");
            $stmt->execute([$text]);
            $message = "Marquee announcement appended.";
        }
        
        // 6. Delete Announcement
        if ($action === 'delete_announcement') {
            $id = intval($_POST['announcement_id'] ?? 0);
            if ($id <= 0) {
                throw new Exception("Invalid announcement.");
            }
            
            $stmt = $pdo->prepare("DELETE FROM `announcements` WHERE `id` = ?");
            $stmt->execute([$id]);
            $message = "Marquee announcement deleted.";
        }
        
        // 7. Add Banner Slider
        if ($action === 'add_banner') {
            $title = trim($_POST['title'] ?? '');
            $link = trim($_POST['link_path'] ?? 'shop.php');
            
            $imgPath = '';
            if (isset($_FILES['banner_file']) && $_FILES['banner_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['banner_file'];
                $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
                $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                if (in_array($fileExt, $allowedExts)) {
                    $destDir = __DIR__ . '/../uploads/';
                    if (!is_dir($destDir)) {
                        mkdir($destDir, 0777, true);
                    }
                    $newFilename = uniqid('banner_') . '.' . $fileExt;
                    if (move_uploaded_file($file['tmp_name'], $destDir . $newFilename)) {
                        $imgPath = 'uploads/' . $newFilename;
                    }
                }
            }
            
            if (empty($imgPath)) {
                $imgPath = trim($_POST['image_path_url'] ?? '');
            }
            
            if (empty($imgPath) || empty($title)) {
                throw new Exception("Banner title and image are required.");
            }
            
            $stmt = $pdo->prepare("INSERT INTO `banners` (`image_path`, `link_path`, `title`) VALUES (?, ?, ?)");
            $stmt->execute([$imgPath, $link, $title]);
            $message = "Marketing banner slider drop successful.";
        }
        
        // 8. Delete Banner
        if ($action === 'delete_banner') {
            $id = intval($_POST['banner_id'] ?? 0);
            if ($id <= 0) {
                throw new Exception("Invalid banner selection.");
            }
            
            $stmt = $pdo->prepare("DELETE FROM `banners` WHERE `id` = ?");
            $stmt->execute([$id]);
            $message = "Marketing banner removed.";
        }

        // 9. Create Admin Account
        if ($action === 'create_admin') {
            $newName     = trim($_POST['new_admin_name'] ?? '');
            $newEmail    = trim($_POST['new_admin_email'] ?? '');
            $newPassword = trim($_POST['new_admin_password'] ?? '');
            createAdminUser($newName, $newEmail, $newPassword);
            $message = "Admin account created for " . htmlspecialchars($newEmail) . ".";
        }

        // 10. Delete Admin Account
        if ($action === 'delete_admin') {
            $targetId = intval($_POST['admin_user_id'] ?? 0);
            if ($targetId <= 0) throw new Exception("Invalid admin selection.");
            deleteAdminUser($targetId, $_SESSION['admin_id']);
            $message = "Admin account removed successfully.";
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// --- FETCH DASHBOARD STATE FROM DATABASE ---

// 1. Stats Aggregation
$totalOrders = $pdo->query("SELECT COUNT(*) FROM `orders`")->fetchColumn();
$pendingOrders = $pdo->query("SELECT COUNT(*) FROM `orders` WHERE `status` = 'Pending'")->fetchColumn();
$paidOrders = $pdo->query("SELECT COUNT(*) FROM `orders` WHERE `status` IN ('Receipt Uploaded', 'Approved', 'Shipped')")->fetchColumn();
$completedRevenue = $pdo->query("SELECT SUM(total) FROM `orders` WHERE `status` IN ('Approved', 'Shipped')")->fetchColumn() ?: 0.00;

// 2. Orders List
$ordersQuery = $pdo->query("SELECT * FROM `orders` ORDER BY id DESC");
$orders = $ordersQuery->fetchAll();

// Map order items for display
$allOrderItems = [];
if (!empty($orders)) {
    foreach ($orders as $o) {
        $itemsStmt = $pdo->prepare("SELECT * FROM `order_items` WHERE `order_id` = ?");
        $itemsStmt->execute([$o['id']]);
        $allOrderItems[$o['id']] = $itemsStmt->fetchAll();
    }
}

// 3. Products List
$productsQuery = $pdo->query("SELECT * FROM `products` ORDER BY id DESC");
$products = $productsQuery->fetchAll();

// 4. Announcements
$announcementsQuery = $pdo->query("SELECT * FROM `announcements` ORDER BY id DESC");
$announcements = $announcementsQuery->fetchAll();

// 5. Banners
$bannersQuery = $pdo->query("SELECT * FROM `banners` ORDER BY id DESC");
$banners = $bannersQuery->fetchAll();

// 6. Contact Inquiries
$inquiriesQuery = $pdo->query("SELECT * FROM `contact_inquiries` ORDER BY id DESC");
$inquiries = $inquiriesQuery->fetchAll();

// 7. Admin Users List
$adminUsersQuery = $pdo->query("SELECT `id`, `name`, `email`, `created_at` FROM `admin_users` ORDER BY id ASC");
$adminUsers = $adminUsersQuery->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVORAA CLOTHING | Admin Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-bg: #0a0a0a;
            --color-surface: #111111;
            --color-surface-hover: #181818;
            --color-border: #222222;
            --color-primary: #c5a880;
            --color-primary-hover: #b8976c;
            --color-text: #e1e1e1;
            --color-text-muted: #7c7c7c;
            --color-success: #52a373;
            --color-error: #cc5a5a;
            --font-serif: 'Cinzel', serif;
            --font-sans: 'Inter', sans-serif;
            --sidebar-width: 260px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--color-bg);
            color: var(--color-text);
            font-family: var(--font-sans);
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR STYLES */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--color-surface);
            border-right: 1px solid var(--color-border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 10;
        }

        .sidebar-brand {
            padding: 30px 24px;
            border-bottom: 1px solid var(--color-border);
            text-align: center;
        }

        .sidebar-brand h1 {
            font-family: var(--font-serif);
            font-size: 20px;
            font-weight: 500;
            letter-spacing: 5px;
            color: #ffffff;
            text-transform: uppercase;
        }

        .sidebar-brand p {
            font-size: 10px;
            color: var(--color-primary);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 4px;
        }

        .sidebar-menu {
            flex-grow: 1;
            padding: 30px 16px;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 14px 16px;
            color: var(--color-text-muted);
            text-decoration: none;
            font-size: 13px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            font-weight: 500;
            margin-bottom: 8px;
            border-left: 2px solid transparent;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .menu-item:hover, .menu-item.active {
            color: #ffffff;
            background-color: rgba(255, 255, 255, 0.02);
            border-left-color: var(--color-primary);
        }

        .sidebar-footer {
            padding: 24px;
            border-top: 1px solid var(--color-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-meta {
            font-size: 11px;
        }

        .admin-meta span {
            display: block;
            color: var(--color-text-muted);
        }

        .admin-meta strong {
            color: #ffffff;
        }

        .logout-btn {
            background: none;
            border: none;
            color: var(--color-error);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            font-weight: 600;
            padding: 4px;
        }

        .logout-btn:hover {
            text-decoration: underline;
        }

        /* MAIN CONTENT AREA */
        .main-content {
            margin-left: var(--sidebar-width);
            flex-grow: 1;
            padding: 40px;
            max-width: 1400px;
            width: calc(100% - var(--sidebar-width));
        }

        .dashboard-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dashboard-title h2 {
            font-family: var(--font-serif);
            font-size: 26px;
            font-weight: 500;
            letter-spacing: 2px;
            color: #ffffff;
        }

        .dashboard-title p {
            font-size: 13px;
            color: var(--color-text-muted);
            margin-top: 4px;
        }

        /* STATS BAR */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background-color: var(--color-surface);
            border: 1px solid var(--color-border);
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            height: 2px;
            width: 30px;
            background-color: var(--color-primary);
        }

        .stat-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--color-text-muted);
            margin-bottom: 12px;
            font-weight: 600;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 400;
            color: #ffffff;
            font-family: var(--font-serif);
        }

        .stat-value.revenue {
            color: var(--color-primary);
        }

        /* ALERTS */
        .alert {
            padding: 16px 20px;
            border-radius: 0px;
            margin-bottom: 30px;
            font-size: 14px;
            letter-spacing: 0.5px;
        }

        .alert-success {
            background-color: rgba(82, 163, 115, 0.08);
            border-left: 3px solid var(--color-success);
            color: var(--color-success);
        }

        .alert-error {
            background-color: rgba(204, 90, 90, 0.08);
            border-left: 3px solid var(--color-error);
            color: var(--color-error);
        }

        /* TAB PANELS */
        .tab-panel {
            display: none;
            animation: fadeIn 0.4s ease;
        }

        .tab-panel.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* SECTION CARD LAYOUT */
        .content-card {
            background-color: var(--color-surface);
            border: 1px solid var(--color-border);
            padding: 30px;
            margin-bottom: 40px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            border-bottom: 1px solid var(--color-border);
            padding-bottom: 16px;
        }

        .card-header h3 {
            font-family: var(--font-serif);
            font-size: 18px;
            font-weight: 500;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        /* TABLES */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        .luxury-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 13px;
        }

        .luxury-table th {
            padding: 16px 20px;
            border-bottom: 2px solid var(--color-border);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--color-text-muted);
            font-weight: 600;
        }

        .luxury-table td {
            padding: 18px 20px;
            border-bottom: 1px solid var(--color-border);
            vertical-align: top;
            color: var(--color-text);
        }

        .luxury-table tr:hover td {
            background-color: var(--color-surface-hover);
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 600;
        }

        .badge-pending { background-color: rgba(220, 180, 50, 0.1); color: #ecdb54; border: 1px solid rgba(220, 180, 50, 0.3); }
        .badge-uploaded { background-color: rgba(120, 180, 255, 0.1); color: #5aa0ff; border: 1px solid rgba(120, 180, 255, 0.3); }
        .badge-approved { background-color: rgba(82, 163, 115, 0.1); color: var(--color-success); border: 1px solid rgba(82, 163, 115, 0.3); }
        .badge-shipped { background-color: rgba(200, 120, 255, 0.1); color: #be7eff; border: 1px solid rgba(200, 120, 255, 0.3); }
        .badge-cancelled { background-color: rgba(204, 90, 90, 0.1); color: var(--color-error); border: 1px solid rgba(204, 90, 90, 0.3); }

        .order-meta-info div {
            margin-bottom: 4px;
        }

        .order-meta-info span {
            color: var(--color-text-muted);
        }

        .order-items-list {
            list-style: none;
            padding: 0;
        }

        .order-items-list li {
            border-bottom: 1px dashed var(--color-border);
            padding: 4px 0;
            font-size: 12px;
        }

        .order-items-list li:last-child {
            border-bottom: none;
        }

        .order-receipt-thumb {
            width: 80px;
            height: 50px;
            object-fit: cover;
            border: 1px solid var(--color-border);
            cursor: pointer;
            transition: opacity 0.2s ease;
        }

        .order-receipt-thumb:hover {
            opacity: 0.8;
        }

        .order-status-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .select-input {
            background-color: var(--color-bg);
            border: 1px solid var(--color-border);
            color: #ffffff;
            padding: 8px 12px;
            outline: none;
            font-size: 12px;
            font-family: var(--font-sans);
            cursor: pointer;
            transition: border 0.2s ease;
        }

        .select-input:focus {
            border-color: var(--color-primary);
        }

        .btn {
            background-color: var(--color-primary);
            color: #000000;
            border: none;
            padding: 10px 18px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            font-family: var(--font-sans);
            transition: all 0.25s ease;
        }

        .btn:hover {
            background-color: var(--color-primary-hover);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 10px;
            letter-spacing: 1px;
        }

        .btn-outline {
            background: none;
            border: 1px solid var(--color-primary);
            color: var(--color-primary);
        }

        .btn-outline:hover {
            background-color: var(--color-primary);
            color: #000000;
        }

        .btn-danger {
            background-color: transparent;
            border: 1px solid var(--color-error);
            color: var(--color-error);
        }

        .btn-danger:hover {
            background-color: var(--color-error);
            color: #ffffff;
        }

        /* FORM CONTROLS */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .form-full-row {
            grid-column: 1 / -1;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--color-text-muted);
            margin-bottom: 8px;
            font-weight: 500;
        }

        .text-input, .textarea-input {
            width: 100%;
            background-color: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--color-border);
            color: #ffffff;
            padding: 12px 16px;
            font-family: var(--font-sans);
            font-size: 13px;
            outline: none;
            transition: all 0.25s ease;
        }

        .text-input:focus, .textarea-input:focus {
            border-color: var(--color-primary);
            background-color: rgba(255, 255, 255, 0.04);
        }

        select.text-input {
            background-color: #1a1a1a;
            cursor: pointer;
        }
        .text-input option, .select-input option {
            background-color: #1a1a1a;
            color: #ffffff;
        }

        .text-input option:hover, .select-input option:hover {
            background-color: var(--color-primary);
            color: #000000;
        }

        .textarea-input {
            height: 100px;
            resize: vertical;
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-size: 13px;
        }

        .checkbox-container input {
            cursor: pointer;
            accent-color: var(--color-primary);
        }

        /* STOCK CONTROLLER */
        .stock-fields {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            background-color: rgba(255, 255, 255, 0.01);
            padding: 15px;
            border: 1px solid var(--color-border);
        }

        .stock-field {
            flex: 1 1 80px;
            text-align: center;
        }

        .stock-field label {
            display: block;
            font-size: 10px;
            color: var(--color-text-muted);
            margin-bottom: 6px;
            font-weight: 600;
        }

        .stock-field input {
            width: 100%;
            background-color: var(--color-bg);
            border: 1px solid var(--color-border);
            color: #ffffff;
            text-align: center;
            padding: 8px 0;
            font-size: 13px;
            outline: none;
        }

        .stock-field input:focus {
            border-color: var(--color-primary);
        }

        /* PRODUCT ROW DISPLAY */
        .prod-image-col {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .prod-thumb {
            width: 50px;
            height: 70px;
            object-fit: cover;
            border: 1px solid var(--color-border);
        }

        /* LIGHTBOX MODAL */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.95);
            z-index: 999;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }

        .modal-content {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
            border: 2px solid var(--color-primary);
        }

        .modal-close {
            position: absolute;
            top: 30px;
            right: 40px;
            color: #ffffff;
            font-size: 30px;
            font-family: var(--font-sans);
            cursor: pointer;
            font-weight: 300;
        }

        .modal-close:hover {
            color: var(--color-primary);
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <h1>Evoraa</h1>
            <p>Admin Suite</p>
        </div>
        
        <nav class="sidebar-menu">
            <div class="menu-item active" onclick="switchTab('orders', this)">Orders Portal</div>
            <div class="menu-item" onclick="switchTab('products', this)">Garment Catalog</div>
            <div class="menu-item" onclick="switchTab('content', this)">Announcements & Banners</div>
            <div class="menu-item" onclick="switchTab('inquiries', this)">Contact Inquiries</div>
            <div class="menu-item" onclick="switchTab('admins', this)">Admin Accounts</div>
            <a href="../index.html" class="menu-item" style="text-decoration:none; color: var(--color-primary); border-left-color: var(--color-primary);">← Back to Website</a>
        </nav>
        
        <div class="sidebar-footer">
            <div class="admin-meta">
                <span>Active Admin</span>
                <strong><?= htmlspecialchars($_SESSION['admin_name']) ?></strong>
            </div>
            <form action="index.php" method="POST">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="logout-btn">Log Out</button>
            </form>
        </div>
    </aside>

    <!-- MAIN INTERFACE -->
    <main class="main-content">
        
        <!-- HEADER -->
        <header class="dashboard-header">
            <div class="dashboard-title">
                <h2>System Management</h2>
                <p>Monitor customer checkouts, handle inventory replenishments, and update announcement lines.</p>
            </div>
        </header>

        <!-- ALERTS -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- STATISTICS GRID -->
        <section class="stats-grid">
            <div class="stat-card">
                <span class="stat-label">Total Invoices</span>
                <span class="stat-value"><?= $totalOrders ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Pending Reviews</span>
                <span class="stat-value"><?= $pendingOrders ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Paid & Active</span>
                <span class="stat-value"><?= $paidOrders ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-label">Approved Revenue</span>
                <span class="stat-value revenue"><?= number_format($completedRevenue, 2) ?> LKR</span>
            </div>
        </section>

        <!-- ======================= TAB: ORDERS ======================= -->
        <section id="tab-orders" class="tab-panel active">
            <div class="content-card">
                <div class="card-header">
                    <h3>Customer Invoices</h3>
                </div>
                
                <div class="table-responsive">
                    <table class="luxury-table">
                        <thead>
                            <tr>
                                <th>Order Hash / Date</th>
                                <th>Customer & Contact</th>
                                <th>Items Purchased</th>
                                <th>Financials</th>
                                <th>Payment / Receipt</th>
                                <th>Action / Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--color-text-muted);">No orders recorded in the system.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $o): ?>
                                    <tr>
                                        <td>
                                            <strong style="color: #ffffff; letter-spacing: 0.5px;"><?= htmlspecialchars(substr($o['order_hash'], 0, 8)) ?>...</strong>
                                            <div style="font-size: 11px; color: var(--color-text-muted); margin-top: 6px;"><?= $o['created_at'] ?></div>
                                        </td>
                                        <td>
                                            <div class="order-meta-info">
                                                <div><strong><?= htmlspecialchars($o['customer_name']) ?></strong></div>
                                                <div><span>Email:</span> <?= htmlspecialchars($o['customer_email']) ?></div>
                                                <div><span>Phone:</span> <?= htmlspecialchars($o['customer_phone']) ?></div>
                                                <div style="font-size: 11px; margin-top: 6px; color: var(--color-text-muted);">
                                                    <?= htmlspecialchars($o['customer_address']) ?>, <?= htmlspecialchars($o['city']) ?> <?= htmlspecialchars($o['zip']) ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <ul class="order-items-list">
                                                <?php foreach ($allOrderItems[$o['id']] as $item): ?>
                                                    <li>
                                                        <?= htmlspecialchars($item['product_name']) ?> 
                                                        [<strong><?= htmlspecialchars($item['size']) ?></strong>] 
                                                        × <?= $item['qty'] ?> 
                                                        (@ <?= number_format($item['price'], 2) ?>)
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </td>
                                        <td>
                                            <div class="order-meta-info">
                                                <div><span>Subtotal:</span> <?= number_format($o['subtotal'], 2) ?> LKR</div>
                                                <div><span>Shipping:</span> <?= number_format($o['shipping_fee'], 2) ?> LKR</div>
                                                <div style="margin-top: 6px; font-weight: bold; color: var(--color-primary);">
                                                    Total: <?= number_format($o['total'], 2) ?> LKR
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="display: block; font-weight: 500; margin-bottom: 6px;"><?= htmlspecialchars($o['payment_method']) ?></span>
                                            <?php if (!empty($o['receipt_path'])): ?>
                                                <?php if (strtolower(pathinfo($o['receipt_path'], PATHINFO_EXTENSION)) === 'pdf'): ?>
                                                    <a href="../<?= htmlspecialchars($o['receipt_path']) ?>" target="_blank" class="btn btn-sm btn-outline">View Receipt PDF</a>
                                                <?php else: ?>
                                                    <img src="../<?= htmlspecialchars($o['receipt_path']) ?>" class="order-receipt-thumb" onclick="openLightbox(this.src)" alt="Bank Receipt Slip">
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span style="font-size: 11px; color: var(--color-text-muted);">No Receipt Uploaded</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="margin-bottom: 12px;">
                                                <?php 
                                                    $sClass = 'badge-pending';
                                                    if ($o['status'] === 'Receipt Uploaded') $sClass = 'badge-uploaded';
                                                    if ($o['status'] === 'Approved') $sClass = 'badge-approved';
                                                    if ($o['status'] === 'Shipped') $sClass = 'badge-shipped';
                                                    if ($o['status'] === 'Cancelled') $sClass = 'badge-cancelled';
                                                ?>
                                                <span class="badge <?= $sClass ?>"><?= htmlspecialchars($o['status']) ?></span>
                                            </div>
                                            <form action="index.php" method="POST" class="order-status-form">
                                                <input type="hidden" name="action" value="update_order_status">
                                                <input type="hidden" name="order_hash" value="<?= htmlspecialchars($o['order_hash']) ?>">
                                                
                                                <select name="status" class="select-input" onchange="this.form.submit()">
                                                    <option value="Pending" <?= $o['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="Receipt Uploaded" <?= $o['status'] === 'Receipt Uploaded' ? 'selected' : '' ?>>Receipt Uploaded</option>
                                                    <option value="Approved" <?= $o['status'] === 'Approved' ? 'selected' : '' ?>>Approve Order</option>
                                                    <option value="Shipped" <?= $o['status'] === 'Shipped' ? 'selected' : '' ?>>Mark Shipped</option>
                                                    <option value="Cancelled" <?= $o['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancel Order</option>
                                                </select>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- ======================= TAB: PRODUCTS ======================= -->
        <section id="tab-products" class="tab-panel">
            
            <!-- Register New Product Card -->
            <div class="content-card">
                <div class="card-header">
                    <h3 id="product-form-title">Register Luxury Garment Drop</h3>
                    <button class="btn btn-sm btn-outline" id="btn-cancel-edit" style="display: none;" onclick="cancelProductEdit()">Cancel Edit</button>
                </div>
                
                <form action="index.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" id="product-action-field" value="add_product">
                    <input type="hidden" name="product_id" id="product-id-field" value="">
                    
                    <div class="form-grid">
                        <div class="input-group">
                            <label for="product_code">Product Code (Unique identifier)</label>
                            <input type="text" id="product_code" name="product_code" class="text-input" required placeholder="prod-008">
                        </div>
                        <div class="input-group">
                            <label for="product_name">Garment Name</label>
                            <input type="text" id="product_name" name="name" class="text-input" required placeholder="Luxe Silk Slip Dress">
                        </div>
                        <div class="input-group">
                            <label for="product_category">Collection Category</label>
                            <select id="product_category" name="category" class="text-input" required>
                                <option value="" disabled selected>— Select a category —</option>
                                <option value="DRESSES">DRESSES</option>
                                <option value="MAXI DRESSES">MAXI DRESSES</option>
                                <option value="TOPS">TOPS</option>
                                <option value="SHIRTS">SHIRTS</option>
                                <option value="PANTS">PANTS</option>
                                <option value="SKIRTS">SKIRTS</option>
                                <option value="SHORTS">SHORTS</option>
                                <option value="SETS">SETS</option>
                                <option value="CLASSICS">CLASSICS</option>
                                <option value="DENIM">DENIM</option>
                                <option value="T-SHIRTS">T-SHIRTS</option>
                            </select>
                        </div>
                    </div>

                    <div class="input-group form-full-row">
                        <label for="product_description">Description / Narrative</label>
                        <textarea id="product_description" name="description" class="textarea-input" required placeholder="Enter organic weaving details, drape styles..."></textarea>
                    </div>

                    <div class="form-grid">
                        <div class="input-group">
                            <label for="original_price">Original Retail Price (LKR)</label>
                            <input type="number" step="0.01" id="original_price" name="original_price" class="text-input" required placeholder="14000">
                        </div>
                        <div class="input-group">
                            <label for="discount_price">Discounted Sale Price (LKR)</label>
                            <input type="number" step="0.01" id="discount_price" name="discount_price" class="text-input" required placeholder="11900">
                        </div>
                        <div class="input-group" style="display: flex; align-items: flex-end; padding-bottom: 12px;">
                            <label class="checkbox-container">
                                <input type="checkbox" id="discount_active" name="discount_active" value="1">
                                Apply Sale Discount Price Active
                            </label>
                        </div>
                        <div class="input-group">
                            <label for="offer_badge">Exclusive Promotional Badge (e.g. SALE, NEW DROP)</label>
                            <input type="text" id="offer_badge" name="offer_badge" class="text-input" placeholder="15% OFF">
                        </div>
                    </div>

                    <!-- IMAGE ASSETS -->
                    <div class="form-grid">
                        <div class="input-group">
                            <label>Primary Image (Image 1) <span style="color:#e74c3c">*required</span></label>
                            <input type="file" name="image1_file" class="text-input" style="padding: 8px 12px; margin-bottom: 8px;">
                            <input type="text" id="image1_url" name="image1_url" class="text-input" placeholder="Or paste asset URL link (fallback)...">
                        </div>
                        <div class="input-group">
                            <label>Hover / Alt Image (Image 2)</label>
                            <input type="file" name="image2_file" class="text-input" style="padding: 8px 12px; margin-bottom: 8px;">
                            <input type="text" id="image2_url" name="image2_url" class="text-input" placeholder="Or paste secondary asset URL link...">
                        </div>
                        <div class="input-group">
                            <label>Gallery Image (Image 3)</label>
                            <input type="file" name="image3_file" class="text-input" style="padding: 8px 12px; margin-bottom: 8px;">
                            <input type="text" id="image3_url" name="image3_url" class="text-input" placeholder="Or paste image 3 URL link...">
                        </div>
                        <div class="input-group">
                            <label>Gallery Image (Image 4)</label>
                            <input type="file" name="image4_file" class="text-input" style="padding: 8px 12px; margin-bottom: 8px;">
                            <input type="text" id="image4_url" name="image4_url" class="text-input" placeholder="Or paste image 4 URL link...">
                        </div>
                    </div>

                    <!-- INVENTORY QUANTITIES -->
                    <div class="input-group">
                        <label>Size Stock Levels</label>
                        <div class="stock-fields">
                            <div class="stock-field">
                                <label for="stock_xs">XS</label>
                                <input type="number" id="stock_xs" name="stock_xs" min="0" value="0">
                            </div>
                            <div class="stock-field">
                                <label for="stock_s">S</label>
                                <input type="number" id="stock_s" name="stock_s" min="0" value="0">
                            </div>
                            <div class="stock-field">
                                <label for="stock_m">M</label>
                                <input type="number" id="stock_m" name="stock_m" min="0" value="0">
                            </div>
                            <div class="stock-field">
                                <label for="stock_l">L</label>
                                <input type="number" id="stock_l" name="stock_l" min="0" value="0">
                            </div>
                            <div class="stock-field">
                                <label for="stock_xl">XL</label>
                                <input type="number" id="stock_xl" name="stock_xl" min="0" value="0">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn" id="product-form-submit-btn">Compile & Publish Garment</button>
                </form>
            </div>

            <!-- Existing Products Catalog -->
            <div class="content-card">
                <div class="card-header">
                    <h3>Published Garments (Catalog)</h3>
                </div>
                
                <div class="table-responsive">
                    <table class="luxury-table">
                        <thead>
                            <tr>
                                <th>Product Details</th>
                                <th>Code / Category</th>
                                <th>Prices (LKR)</th>
                                <th>Stock (XS | S | M | L | XL)</th>
                                <th>Promotional Badges</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--color-text-muted);">No garments added to the catalog yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $p): ?>
                                    <tr>
                                        <td>
                                            <div class="prod-image-col">
                                                <div style="display:flex;gap:4px;margin-bottom:4px;">
                                                    <?php foreach (['image1','image2','image3','image4'] as $imgKey): ?>
                                                        <?php if (!empty($p[$imgKey])): ?>
                                                            <img src="<?= (strpos($p[$imgKey], 'http') === 0) ? htmlspecialchars($p[$imgKey]) : '../' . htmlspecialchars($p[$imgKey]) ?>" 
                                                                 style="width:38px;height:38px;object-fit:cover;border-radius:4px;border:1px solid #333;" 
                                                                 alt="Image <?= $imgKey ?>">
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                                <div>
                                                    <strong style="color: #ffffff; font-size: 14px;"><?= htmlspecialchars($p['name']) ?></strong>
                                                    <div style="font-size: 11px; color: var(--color-text-muted); max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-top: 4px;">
                                                        <?= htmlspecialchars($p['description']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>Code: <strong><?= htmlspecialchars($p['product_code']) ?></strong></div>
                                            <div style="font-size: 11px; color: var(--color-primary); text-transform: uppercase; margin-top: 4px;"><?= htmlspecialchars($p['category']) ?></div>
                                        </td>
                                        <td>
                                            <div>Retail: <?= number_format($p['original_price'], 2) ?></div>
                                            <?php if ($p['discount_active']): ?>
                                                <div style="color: var(--color-success); font-size: 12px; font-weight: 500; margin-top: 4px;">
                                                    Discount: <?= number_format($p['discount_price'], 2) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="font-family: monospace; letter-spacing: 1px;">
                                                <?= $p['stock_xs'] ?> | <?= $p['stock_s'] ?> | <?= $p['stock_m'] ?> | <?= $p['stock_l'] ?> | <?= $p['stock_xl'] ?>
                                            </div>
                                            <div style="font-size: 10px; color: var(--color-text-muted); margin-top: 4px;">
                                                Total Stock: <?= ($p['stock_xs'] + $p['stock_s'] + $p['stock_m'] + $p['stock_l'] + $p['stock_xl']) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if (!empty($p['offer_badge'])): ?>
                                                <span class="badge" style="background-color: var(--color-primary); color: #000; font-size: 9px; font-weight: bold;"><?= htmlspecialchars($p['offer_badge']) ?></span>
                                            <?php else: ?>
                                                <span style="font-size: 11px; color: var(--color-text-muted);">None</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 8px;">
                                                <button class="btn btn-sm btn-outline" 
                                                        onclick='populateProductEdit(<?= json_encode($p) ?>)'>
                                                    Edit
                                                </button>
                                                
                                                <form action="index.php" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this product?');">
                                                    <input type="hidden" name="action" value="delete_product">
                                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- ======================= TAB: CONTENT ======================= -->
        <section id="tab-content" class="tab-panel">
            <div class="form-grid">
                
                <!-- Rotating Announcements Card -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>Marquee Announcements</h3>
                    </div>
                    
                    <form action="index.php" method="POST" style="margin-bottom: 24px;">
                        <input type="hidden" name="action" value="add_announcement">
                        <div class="input-group">
                            <label for="announcement_text">New Marquee Notification Line</label>
                            <input type="text" id="announcement_text" name="text" class="text-input" required placeholder="e.g. EXTRA 10% OFF WITH BANK TRANSFER SLIPS">
                        </div>
                        <button type="submit" class="btn">Append Marquee Line</button>
                    </form>
                    
                    <div class="table-responsive">
                        <table class="luxury-table">
                            <thead>
                                <tr>
                                    <th>Text Notification Line</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($announcements)): ?>
                                    <tr>
                                        <td colspan="2" style="text-align: center; color: var(--color-text-muted);">No marquee lines compiled.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($announcements as $ann): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($ann['text']) ?></td>
                                            <td style="width: 80px;">
                                                <form action="index.php" method="POST" onsubmit="return confirm('Delete announcement?');">
                                                    <input type="hidden" name="action" value="delete_announcement">
                                                    <input type="hidden" name="announcement_id" value="<?= $ann['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Slider Banners Card -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>Marketing Banner Carousel</h3>
                    </div>
                    
                    <form action="index.php" method="POST" enctype="multipart/form-data" style="margin-bottom: 24px;">
                        <input type="hidden" name="action" value="add_banner">
                        <div class="input-group">
                            <label for="banner_title">Slider Title Text</label>
                            <input type="text" id="banner_title" name="title" class="text-input" required placeholder="e.g. LUXURY APPAREL COLLECTIVE">
                        </div>
                        <div class="input-group">
                            <label for="banner_link">Navigation URL Target</label>
                            <input type="text" id="banner_link" name="link_path" class="text-input" value="shop.php">
                        </div>
                        <div class="input-group">
                            <label>Banner Background Image</label>
                            <input type="file" name="banner_file" class="text-input" style="padding: 8px 12px; margin-bottom: 8px;">
                            <input type="text" name="image_path_url" class="text-input" placeholder="Or paste unsplash image link (fallback)...">
                        </div>
                        <button type="submit" class="btn">Deploy New Slider Banner</button>
                    </form>
                    
                    <div class="table-responsive">
                        <table class="luxury-table">
                            <thead>
                                <tr>
                                    <th>Banner Details</th>
                                    <th>Link Path</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($banners)): ?>
                                    <tr>
                                        <td colspan="3" style="text-align: center; color: var(--color-text-muted);">No slider banners created.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($banners as $b): ?>
                                        <tr>
                                            <td>
                                                <div style="display: flex; gap: 10px; align-items: center;">
                                                    <img src="<?= (strpos($b['image_path'], 'http') === 0) ? htmlspecialchars($b['image_path']) : '../' . htmlspecialchars($b['image_path']) ?>" class="prod-thumb" style="width: 80px; height: 50px; object-fit: cover;" alt="Banner Slide">
                                                    <strong><?= htmlspecialchars($b['title']) ?></strong>
                                                </div>
                                            </td>
                                            <td><code><?= htmlspecialchars($b['link_path']) ?></code></td>
                                            <td style="width: 80px;">
                                                <form action="index.php" method="POST" onsubmit="return confirm('Remove banner slide?');">
                                                    <input type="hidden" name="action" value="delete_banner">
                                                    <input type="hidden" name="banner_id" value="<?= $b['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </section>

        <!-- ======================= TAB: CONTACT INQUIRIES ======================= -->
        <section id="tab-inquiries" class="tab-panel">
            <div class="content-card">
                <div class="card-header">
                    <h3>Customer Contact Enquiries</h3>
                    <span style="font-size:12px;color:var(--color-text-muted);"><?= count($inquiries) ?> total message(s)</span>
                </div>

                <div class="table-responsive">
                    <table class="luxury-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Message</th>
                                <th>Received</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($inquiries)): ?>
                                <tr>
                                    <td colspan="5" style="text-align:center;color:var(--color-text-muted);">No enquiries received yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($inquiries as $inq): ?>
                                    <tr>
                                        <td style="color:var(--color-text-muted);font-size:12px;">#<?= $inq['id'] ?></td>
                                        <td><strong><?= htmlspecialchars($inq['name']) ?></strong></td>
                                        <td>
                                            <a href="mailto:<?= htmlspecialchars($inq['email']) ?>" style="color:var(--color-primary);text-decoration:none;">
                                                <?= htmlspecialchars($inq['email']) ?>
                                            </a>
                                        </td>
                                        <td style="max-width:400px;line-height:1.6;font-size:13px;"><?= nl2br(htmlspecialchars($inq['message'])) ?></td>
                                        <td style="font-size:11px;color:var(--color-text-muted);"><?= $inq['created_at'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- ======================= TAB: ADMIN ACCOUNTS ======================= -->
        <section id="tab-admins" class="tab-panel">

            <!-- Create New Admin Card -->
            <div class="content-card">
                <div class="card-header">
                    <h3>Create New Admin Account</h3>
                </div>

                <form action="index.php" method="POST">
                    <input type="hidden" name="action" value="create_admin">
                    <div class="form-grid">
                        <div class="input-group">
                            <label for="new_admin_name">Full Name</label>
                            <input type="text" id="new_admin_name" name="new_admin_name" class="text-input" required placeholder="e.g. Sarah Manager">
                        </div>
                        <div class="input-group">
                            <label for="new_admin_email">Email Address</label>
                            <input type="email" id="new_admin_email" name="new_admin_email" class="text-input" required placeholder="sarah@evoraa.com">
                        </div>
                        <div class="input-group">
                            <label for="new_admin_password">Password <span style="color:var(--color-text-muted);font-size:10px;">(min. 8 characters)</span></label>
                            <input type="password" id="new_admin_password" name="new_admin_password" class="text-input" required placeholder="••••••••" minlength="8">
                        </div>
                    </div>
                    <button type="submit" class="btn">Create Admin Account</button>
                </form>
            </div>

            <!-- Existing Admins Table -->
            <div class="content-card">
                <div class="card-header">
                    <h3>Active Admin Accounts</h3>
                    <span style="font-size:12px;color:var(--color-text-muted);"><?= count($adminUsers) ?> account(s)</span>
                </div>

                <div class="table-responsive">
                    <table class="luxury-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($adminUsers as $au): ?>
                                <tr <?= ($au['id'] == $_SESSION['admin_id']) ? 'style="background-color:rgba(197,168,128,0.04);"' : '' ?>>
                                    <td style="color:var(--color-text-muted);font-size:12px;">#<?= $au['id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($au['name']) ?></strong>
                                        <?php if ($au['id'] == $_SESSION['admin_id']): ?>
                                            <span style="margin-left:8px;font-size:9px;background:rgba(197,168,128,0.15);color:var(--color-primary);padding:2px 8px;letter-spacing:1px;text-transform:uppercase;">YOU</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color:var(--color-text-muted);"><?= htmlspecialchars($au['email']) ?></td>
                                    <td style="font-size:11px;color:var(--color-text-muted);"><?= $au['created_at'] ?></td>
                                    <td>
                                        <?php if ($au['id'] != $_SESSION['admin_id']): ?>
                                            <form action="index.php" method="POST" onsubmit="return confirm('Remove admin account for <?= htmlspecialchars(addslashes($au['name'])) ?>? This cannot be undone.');">
                                                <input type="hidden" name="action" value="delete_admin">
                                                <input type="hidden" name="admin_user_id" value="<?= $au['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="font-size:11px;color:var(--color-text-muted);">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </section>

    </main>

    <!-- LIGHTBOX MODAL FOR BANK SLIPS -->
    <div id="lightbox" class="modal" onclick="closeLightbox()">
        <span class="modal-close" onclick="closeLightbox()">&times;</span>
        <img class="modal-content" id="lightbox-img" alt="Enlarged Receipt Document">
    </div>

    <!-- TABS CONTROLLER SCRIPT -->
    <script>
        function switchTab(tabId, menuItem) {
            // Hide all tab panels
            document.querySelectorAll('.tab-panel').forEach(panel => {
                panel.classList.remove('active');
            });
            
            // Show requested tab panel
            document.getElementById('tab-' + tabId).classList.add('active');
            
            // Deactivate all sidebar items
            document.querySelectorAll('.menu-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Activate selected sidebar item
            menuItem.classList.add('active');
        }

        // Product Form Populate Edit Helper
        function populateProductEdit(product) {
            document.getElementById('product-form-title').innerText = "Modify Luxury Garment - " + product.name;
            document.getElementById('product-form-submit-btn').innerText = "Update & Commit Garment";
            document.getElementById('product-action-field').value = "edit_product";
            document.getElementById('product-id-field').value = product.id;
            
            document.getElementById('product_code').value = product.product_code;
            document.getElementById('product_name').value = product.name;
            document.getElementById('product_category').value = product.category;
            document.getElementById('product_description').value = product.description;
            document.getElementById('original_price').value = product.original_price;
            document.getElementById('discount_price').value = product.discount_price;
            document.getElementById('offer_badge').value = product.offer_badge || '';
            
            document.getElementById('discount_active').checked = product.discount_active == 1;
            
            // Pre-fill image URL fallback fields so existing images are preserved on edit
            document.getElementById('image1_url').value = product.image1 || '';
            document.getElementById('image2_url').value = product.image2 || '';
            document.getElementById('image3_url').value = product.image3 || '';
            document.getElementById('image4_url').value = product.image4 || '';
            
            document.getElementById('stock_xs').value = product.stock_xs;
            document.getElementById('stock_s').value = product.stock_s;
            document.getElementById('stock_m').value = product.stock_m;
            document.getElementById('stock_l').value = product.stock_l;
            document.getElementById('stock_xl').value = product.stock_xl;
            
            // Show cancel edit button
            document.getElementById('btn-cancel-edit').style.display = "inline-block";
            
            // Smoothly scroll to product form
            document.getElementById('product-form-title').scrollIntoView({ behavior: 'smooth' });
        }

        function cancelProductEdit() {
            document.getElementById('product-form-title').innerText = "Register Luxury Garment Drop";
            document.getElementById('product-form-submit-btn').innerText = "Compile & Publish Garment";
            document.getElementById('product-action-field').value = "add_product";
            document.getElementById('product-id-field').value = "";
            
            // Reset form
            document.getElementById('product_code').value = '';
            document.getElementById('product_name').value = '';
            document.getElementById('product_category').value = '';
            document.getElementById('product_description').value = '';
            document.getElementById('original_price').value = '';
            document.getElementById('discount_price').value = '';
            document.getElementById('offer_badge').value = '';
            document.getElementById('discount_active').checked = false;
            
            document.getElementById('stock_xs').value = 0;
            document.getElementById('stock_s').value = 0;
            document.getElementById('stock_m').value = 0;
            document.getElementById('stock_l').value = 0;
            document.getElementById('stock_xl').value = 0;
            
            document.getElementById('btn-cancel-edit').style.display = "none";
        }

        // Lightbox controllers
        function openLightbox(src) {
            const lightbox = document.getElementById('lightbox');
            const img = document.getElementById('lightbox-img');
            img.src = src;
            lightbox.style.display = "flex";
        }

        function closeLightbox() {
            document.getElementById('lightbox').style.display = "none";
        }
    </script>

</body>
</html>

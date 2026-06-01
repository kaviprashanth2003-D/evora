<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/config/db.php';

$action = $_GET['action'] ?? '';

try {
    $pdo = getDBConnection();
    
    switch ($action) {
        case 'get_shop_data':
            handleGetShopData($pdo);
            break;
            
        case 'checkout':
            handleCheckout($pdo);
            break;
            
        case 'track_order':
            handleTrackOrder($pdo);
            break;
            
        case 'upload_receipt':
            handleUploadReceipt($pdo);
            break;

        case 'register_customer':
            handleRegisterCustomer($pdo);
            break;

        case 'login':
            handleLogin($pdo);
            break;

        case 'submit_feedback':
            handleSubmitFeedback($pdo);
            break;

        case 'submit_contact':
            handleSubmitContact($pdo);
            break;

        case 'get_customer_dashboard':
            handleGetCustomerDashboard($pdo);
            break;
        case 'get_testimonials':
            handleGetTestimonials($pdo);
            break;
            
        default:
            sendResponse(400, ["error" => "Invalid action parameter."]);
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    sendResponse(500, ["error" => "Internal server error: " . $e->getMessage()]);
}

/**
 * Sends a structured JSON HTTP response.
 */
function sendResponse($statusCode, $data) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

/**
 * Returns marquee announcements, slider banners, and current product inventory.
 */
function handleGetShopData($pdo) {
    // 1. Announcements
    $annStmt = $pdo->query("SELECT * FROM `announcements` ORDER BY id DESC");
    $announcements = $annStmt->fetchAll();
    
    // 2. Banners
    $bannerStmt = $pdo->query("SELECT * FROM `banners` ORDER BY id DESC");
    $banners = $bannerStmt->fetchAll();
    
    // 3. Products
    $productStmt = $pdo->query("SELECT * FROM `products` ORDER BY id DESC");
    $products = $productStmt->fetchAll();
    
    sendResponse(200, [
        "announcements" => array_column($announcements, 'text'),
        "banners" => $banners,
        "products" => $products
    ]);
}

/**
 * Handles order checkout with atomic stock deductions and server-side calculations.
 */
function handleCheckout($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(405, ["error" => "Method not allowed. Use POST."]);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        sendResponse(400, ["error" => "Invalid JSON payload."]);
    }
    
    // Validate inputs
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $address = trim($input['address'] ?? '');
    $city = trim($input['city'] ?? '');
    $zip = trim($input['zip'] ?? '');
    $deliveryTier = trim($input['delivery_tier'] ?? 'Standard');
    $paymentMethod = trim($input['payment_method'] ?? 'COD');
    $cart = $input['cart'] ?? [];
    
    if (empty($name) || empty($email) || empty($phone) || empty($address) || empty($city) || empty($cart)) {
        sendResponse(400, ["error" => "Missing required customer details or empty cart."]);
    }
    
    // Verify email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(400, ["error" => "Invalid email address format."]);
    }
    
    // Validate delivery tier and payment method
    if (!in_array($deliveryTier, ['Standard', 'Express'])) {
        sendResponse(400, ["error" => "Invalid delivery option."]);
    }
    if (!in_array($paymentMethod, ['COD', 'Bank Transfer'])) {
        sendResponse(400, ["error" => "Invalid payment method."]);
    }
    
    // Begin transaction for inventory locking and order creation
    $pdo->beginTransaction();
    
    try {
        $subtotal = 0;
        $discountAmount = 0;
        $itemsToInsert = [];
        
        foreach ($cart as $item) {
            $productId = intval($item['id'] ?? 0);
            $size = strtoupper(trim($item['size'] ?? ''));
            $qty = intval($item['qty'] ?? 0);
            
            if ($productId <= 0 || $qty <= 0 || !in_array($size, ['XS', 'S', 'M', 'L', 'XL'])) {
                throw new Exception("Invalid item or size in cart selection.");
            }
            
            // Check product exists and fetch details
            $prodStmt = $pdo->prepare("SELECT * FROM `products` WHERE `id` = ? FOR UPDATE");
            $prodStmt->execute([$productId]);
            $product = $prodStmt->fetch();
            
            if (!$product) {
                throw new Exception("Product ID $productId not found.");
            }
            
            // Validate size stock
            $stockField = 'stock_' . strtolower($size);
            $availableStock = intval($product[$stockField] ?? 0);
            
            if ($availableStock < $qty) {
                throw new Exception("Insufficient stock for product '{$product['name']}' in size {$size}. Available: {$availableStock}");
            }
            
            // Calculate prices
            $price = floatval($product['discount_active'] ? $product['discount_price'] : $product['original_price']);
            $itemSubtotal = $price * $qty;
            $subtotal += $itemSubtotal;
            
            // If discount is active, track discount amount
            if ($product['discount_active']) {
                $discountDiff = floatval($product['original_price']) - floatval($product['discount_price']);
                $discountAmount += ($discountDiff * $qty);
            }
            
            $itemsToInsert[] = [
                "product_id" => $product['id'],
                "name" => $product['name'],
                "size" => $size,
                "qty" => $qty,
                "price" => $price,
                "stock_field" => $stockField,
                "new_stock" => $availableStock - $qty
            ];
        }
        
        // Shipping fee calculation: free if subtotal >= 15000 LKR
        $shippingFee = 0;
        if ($subtotal < 15000) {
            $shippingFee = ($deliveryTier === 'Express') ? 700.00 : 350.00;
        }
        
        $total = $subtotal + $shippingFee;
        
        // Generate secure 32-character hex token for order lookup
        $orderHash = bin2hex(random_bytes(16));
        
        // Insert order record
        $orderStmt = $pdo->prepare("INSERT INTO `orders` 
            (`order_hash`, `customer_name`, `customer_email`, `customer_phone`, `customer_address`, `city`, `zip`, `delivery_tier`, `shipping_fee`, `payment_method`, `subtotal`, `discount_amount`, `total`, `status`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
            
        $orderStmt->execute([
            $orderHash, $name, $email, $phone, $address, $city, $zip, $deliveryTier, $shippingFee, $paymentMethod, $subtotal, $discountAmount, $total
        ]);
        
        $orderId = $pdo->lastInsertId();
        
        // Insert order items and deduct inventory stock
        $itemInsertStmt = $pdo->prepare("INSERT INTO `order_items` 
            (`order_id`, `product_id`, `product_name`, `size`, `qty`, `price`) 
            VALUES (?, ?, ?, ?, ?, ?)");
            
        foreach ($itemsToInsert as $item) {
            // Write item breakdown
            $itemInsertStmt->execute([
                $orderId, $item['product_id'], $item['name'], $item['size'], $item['qty'], $item['price']
            ]);
            
            // Deduct stock (dynamic query injection safe since we validated stock_field is one of the hardcoded stock_* fields)
            $updateSql = "UPDATE `products` SET `{$item['stock_field']}` = ? WHERE `id` = ?";
            $pdo->prepare($updateSql)->execute([$item['new_stock'], $item['product_id']]);
        }
        
        $pdo->commit();
        
        sendResponse(200, [
            "success" => true,
            "order_hash" => $orderHash,
            "total" => $total,
            "shipping_fee" => $shippingFee,
            "payment_method" => $paymentMethod
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        sendResponse(400, ["error" => $e->getMessage()]);
    }
}

/**
 * Tracks an order status and returns complete line items.
 */
function handleTrackOrder($pdo) {
    $hash = trim($_GET['hash'] ?? '');
    
    if (empty($hash)) {
        sendResponse(400, ["error" => "Missing order hash parameter."]);
    }
    
    $orderStmt = $pdo->prepare("SELECT * FROM `orders` WHERE `order_hash` = ?");
    $orderStmt->execute([$hash]);
    $order = $orderStmt->fetch();
    
    if (!$order) {
        sendResponse(404, ["error" => "Order not found."]);
    }
    
    // Fetch items
    $itemsStmt = $pdo->prepare("SELECT oi.*, p.image1 FROM `order_items` oi
        LEFT JOIN `products` p ON oi.product_id = p.id
        WHERE oi.order_id = ?");
    $itemsStmt->execute([$order['id']]);
    $items = $itemsStmt->fetchAll();
    
    // Omit sensitive database IDs
    unset($order['id']);
    foreach ($items as &$item) {
        unset($item['id']);
        unset($item['order_id']);
    }
    
    sendResponse(200, [
        "success" => true,
        "order" => $order,
        "items" => $items
    ]);
}

/**
 * Uploads bank transfer receipt and attaches it to order.
 */
function handleUploadReceipt($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(405, ["error" => "Method not allowed. Use POST."]);
    }
    
    $hash = trim($_POST['order_hash'] ?? '');
    if (empty($hash)) {
        sendResponse(400, ["error" => "Missing order hash."]);
    }
    
    // Check order exists and is eligible for receipt upload
    $orderStmt = $pdo->prepare("SELECT * FROM `orders` WHERE `order_hash` = ?");
    $orderStmt->execute([$hash]);
    $order = $orderStmt->fetch();
    
    if (!$order) {
        sendResponse(404, ["error" => "Order not found."]);
    }
    
    if ($order['payment_method'] !== 'Bank Transfer') {
        sendResponse(400, ["error" => "This order did not select Bank Transfer as the payment method."]);
    }
    
    if (!isset($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
        sendResponse(400, ["error" => "Please upload a valid receipt file."]);
    }
    
    $file = $_FILES['receipt'];
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        sendResponse(400, ["error" => "Receipt size exceeds maximum limit of 5MB."]);
    }
    
    // Validate file extension and MIME type
    $allowedExts = ['jpg', 'jpeg', 'png', 'pdf'];
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowedMimes = ['image/jpeg', 'image/png', 'application/pdf'];
    
    if (!in_array($fileExt, $allowedExts) || !in_array($mimeType, $allowedMimes)) {
        sendResponse(400, ["error" => "Unsupported file format. Only JPG, PNG, and PDF receipt slips are allowed."]);
    }
    
    // Generate clean filename
    $destDir = __DIR__ . '/uploads/receipts/';
    if (!is_dir($destDir)) {
        mkdir($destDir, 0777, true);
    }
    
    $newFilename = $hash . '_' . time() . '.' . $fileExt;
    $destPath = $destDir . $newFilename;
    
    if (move_uploaded_file($file['tmp_name'], $destPath)) {
        // Update database
        $updateStmt = $pdo->prepare("UPDATE `orders` SET `receipt_path` = ?, `status` = 'Receipt Uploaded' WHERE `order_hash` = ?");
        $updateStmt->execute(['uploads/receipts/' . $newFilename, $hash]);
        
        sendResponse(200, [
            "success" => true,
            "message" => "Payment receipt uploaded successfully. Our team will verify it shortly."
        ]);
    } else {
        sendResponse(500, ["error" => "Failed to save file onto the server."]);
    }
}

/**
 * Registers a new customer account with bcrypt hashed password.
 */
function handleRegisterCustomer($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(405, ["error" => "Method not allowed. Use POST."]);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        sendResponse(400, ["error" => "Invalid JSON payload."]);
    }

    $name     = trim($input['name']     ?? '');
    $email    = trim($input['email']    ?? '');
    $password = trim($input['password'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        sendResponse(400, ["error" => "Name, email and password are required."]);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(400, ["error" => "Invalid email address format."]);
    }
    if (strlen($password) < 6) {
        sendResponse(400, ["error" => "Password must be at least 6 characters."]);
    }

    // Check duplicate email
    $check = $pdo->prepare("SELECT id FROM `customers` WHERE `email` = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        sendResponse(409, ["error" => "An account already exists with this email address."]);
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO `customers` (`name`, `email`, `password_hash`) VALUES (?, ?, ?)");
    $stmt->execute([$name, $email, $hash]);

    sendResponse(200, ["success" => true, "message" => "Account created successfully.", "name" => $name, "email" => $email]);
}

/**
 * Unified login: checks admin_users first, then customers.
 * Returns redirect=true for admin, or customer name for storefront login.
 */
function handleLogin($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(405, ["error" => "Method not allowed. Use POST."]);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        sendResponse(400, ["error" => "Invalid JSON payload."]);
    }

    $email    = trim($input['email']    ?? '');
    $password = trim($input['password'] ?? '');

    if (empty($email) || empty($password)) {
        sendResponse(400, ["error" => "Email and password are required."]);
    }

    // 1. Check admin accounts first
    $adminStmt = $pdo->prepare("SELECT * FROM `admin_users` WHERE `email` = ?");
    $adminStmt->execute([$email]);
    $admin = $adminStmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        sendResponse(200, [
            "success"   => true,
            "role"      => "admin",
            "redirect"  => "admin/index.php",
            "name"      => $admin['name']
        ]);
    }

    // 2. Check customer accounts
    $custStmt = $pdo->prepare("SELECT * FROM `customers` WHERE `email` = ?");
    $custStmt->execute([$email]);
    $customer = $custStmt->fetch();

    if ($customer && password_verify($password, $customer['password_hash'])) {
        sendResponse(200, [
            "success"   => true,
            "role"      => "customer",
            "name"      => $customer['name'],
            "email"     => $customer['email'],
            "id"        => $customer['id']
        ]);
    }

    sendResponse(401, ["error" => "Invalid email address or password. Please try again."]);
}

/**
 * Submits a new customer feedback entry.
 */
function handleSubmitFeedback($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(405, ["error" => "Method not allowed. Use POST."]);
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        sendResponse(400, ["error" => "Invalid JSON payload."]);
    }

    $name    = trim($input['name']    ?? '');
    $rating  = intval($input['rating']  ?? 0);
    $comment = trim($input['comment'] ?? '');

    if (empty($name) || empty($comment)) {
        sendResponse(400, ["error" => "Name and comment are required."]);
    }
    if ($rating < 1 || $rating > 5) {
        sendResponse(400, ["error" => "Rating must be between 1 and 5."]);
    }

    $stmt = $pdo->prepare("INSERT INTO `feedback` (`customer_name`, `rating`, `comment`) VALUES (?, ?, ?)");
    $stmt->execute([$name, $rating, $comment]);

    sendResponse(200, ["success" => true, "message" => "Thank you for your review!"]);
}

/**
 * Returns approved customer testimonials for the storefront.
 */
function handleGetTestimonials($pdo) {
    $stmt = $pdo->query("SELECT `customer_name`, `rating`, `comment`, `created_at` FROM `feedback` WHERE `approved` = 1 ORDER BY `id` DESC LIMIT 9");
    $reviews = $stmt->fetchAll();
    sendResponse(200, ["success" => true, "testimonials" => $reviews]);
}

/**
 * Saves a contact form enquiry into contact_inquiries table.
 */
function handleSubmitContact($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(405, ["error" => "Method not allowed."]);
    }
    $input = json_decode(file_get_contents('php://input'), true);
    $name    = trim($input['name']    ?? '');
    $email   = trim($input['email']   ?? '');
    $message = trim($input['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        sendResponse(400, ["error" => "All fields are required."]);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(400, ["error" => "Invalid email address."]);
    }

    $stmt = $pdo->prepare("INSERT INTO `contact_inquiries` (`name`, `email`, `message`) VALUES (?, ?, ?)");
    $stmt->execute([$name, $email, $message]);
    sendResponse(200, ["success" => true, "message" => "Enquiry submitted successfully."]);
}

/**
 * Returns logged-in customer's orders and wishlist product details.
 * Requires customer_id passed as POST JSON.
 */
function handleGetCustomerDashboard($pdo) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendResponse(405, ["error" => "Method not allowed."]);
    }
    $input = json_decode(file_get_contents('php://input'), true);
    $customerId = intval($input['customer_id'] ?? 0);
    $email      = trim($input['email'] ?? '');

    if ($customerId <= 0 || empty($email)) {
        sendResponse(400, ["error" => "Customer ID and email required."]);
    }

    // Verify customer exists
    $check = $pdo->prepare("SELECT id, name, email FROM `customers` WHERE `id` = ? AND `email` = ?");
    $check->execute([$customerId, $email]);
    $customer = $check->fetch();
    if (!$customer) {
        sendResponse(401, ["error" => "Unauthorized."]);
    }

    // Fetch orders by customer email
    $ordersStmt = $pdo->prepare("SELECT * FROM `orders` WHERE `customer_email` = ? ORDER BY id DESC");
    $ordersStmt->execute([$email]);
    $orders = $ordersStmt->fetchAll();

    // Attach order items
    foreach ($orders as &$order) {
        $itemsStmt = $pdo->prepare("SELECT * FROM `order_items` WHERE `order_id` = ?");
        $itemsStmt->execute([$order['id']]);
        $order['items'] = $itemsStmt->fetchAll();
    }

    // Fetch wishlist product details (product IDs passed from frontend)
    $wishlistIds = $input['wishlist_ids'] ?? [];
    $wishlistProducts = [];
    if (!empty($wishlistIds)) {
        $placeholders = implode(',', array_fill(0, count($wishlistIds), '?'));
        $wStmt = $pdo->prepare("SELECT id, name, image1, original_price, discount_price, discount_active, category FROM `products` WHERE `id` IN ($placeholders)");
        $wStmt->execute($wishlistIds);
        $wishlistProducts = $wStmt->fetchAll();
    }

    sendResponse(200, [
        "success"  => true,
        "customer" => $customer,
        "orders"   => $orders,
        "wishlist" => $wishlistProducts
    ]);
}
?>

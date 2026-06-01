<?php
// Start secure session if not already active
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}

require_once __DIR__ . '/../config/db.php';

/**
 * Validates the current admin session, preventing session hijacking.
 */
function requireAdminAuth($isAjax = false) {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        handleUnauthorized($isAjax);
    }
    $expectedFingerprint = md5($_SERVER['HTTP_USER_AGENT'] . getClientIP());
    if (!isset($_SESSION['user_fingerprint']) || $_SESSION['user_fingerprint'] !== $expectedFingerprint) {
        logoutAdmin();
        handleUnauthorized($isAjax);
    }
    if (!isset($_SESSION['last_regeneration']) || (time() - $_SESSION['last_regeneration']) > 900) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

function handleUnauthorized($isAjax) {
    if ($isAjax) {
        header("Content-Type: application/json; charset=UTF-8");
        http_response_code(401);
        echo json_encode(["error" => "Unauthorized access. Please log in."]);
        exit();
    } else {
        header("Location: login.php");
        exit();
    }
}

/**
 * Attempts to authenticate an administrator.
 */
function loginAdmin($email, $password) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM `admin_users` WHERE `email` = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id']        = $user['id'];
        $_SESSION['admin_email']     = $user['email'];
        $_SESSION['admin_name']      = $user['name'];
        $_SESSION['last_regeneration'] = time();
        $_SESSION['user_fingerprint']  = md5($_SERVER['HTTP_USER_AGENT'] . getClientIP());
        return true;
    }
    return false;
}

/**
 * Creates a new admin account. Returns true on success, throws Exception on failure.
 */
function createAdminUser($name, $email, $password) {
    if (empty($name) || empty($email) || empty($password)) {
        throw new Exception("Name, email, and password are all required.");
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Please enter a valid email address.");
    }
    if (strlen($password) < 8) {
        throw new Exception("Password must be at least 8 characters long.");
    }
    $pdo = getDBConnection();
    // Check for duplicate email
    $stmt = $pdo->prepare("SELECT id FROM `admin_users` WHERE `email` = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        throw new Exception("An admin with this email already exists.");
    }
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO `admin_users` (`name`, `email`, `password_hash`) VALUES (?, ?, ?)");
    $stmt->execute([$name, $email, $hash]);
    return true;
}

/**
 * Deletes an admin account. Cannot delete the currently logged-in admin.
 */
function deleteAdminUser($targetId, $currentAdminId) {
    if ((int)$targetId === (int)$currentAdminId) {
        throw new Exception("You cannot delete your own account.");
    }
    $pdo = getDBConnection();
    // Ensure at least one admin will remain
    $count = $pdo->query("SELECT COUNT(*) FROM `admin_users`")->fetchColumn();
    if ($count <= 1) {
        throw new Exception("Cannot delete the last remaining admin account.");
    }
    $stmt = $pdo->prepare("DELETE FROM `admin_users` WHERE `id` = ?");
    $stmt->execute([$targetId]);
    return true;
}

/**
 * Logs out the administrator.
 */
function logoutAdmin() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]);
    }
    session_destroy();
}

function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
?>

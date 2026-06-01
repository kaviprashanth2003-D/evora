<?php
require_once __DIR__ . '/auth.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        if (loginAdmin($email, $password)) {
            header("Location: index.php");
            exit();
        } else {
            $error = 'Invalid email address or security password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EVORAA CLOTHING | Admin Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --color-bg: #0d0d0d;
            --color-surface: #141414;
            --color-border: #262626;
            --color-primary: #c5a880; /* Elegant bronze/gold */
            --color-primary-hover: #b8976c;
            --color-text: #e5e5e5;
            --color-text-muted: #8c8c8c;
            --color-error: #e06c75;
            --font-serif: 'Cinzel', serif;
            --font-sans: 'Inter', sans-serif;
            --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
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
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow: hidden;
            position: relative;
        }

        /* Ambient Glow Behind Form */
        body::before {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(197, 168, 128, 0.08) 0%, rgba(0, 0, 0, 0) 70%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 0;
            pointer-events: none;
        }

        .login-container {
            width: 100%;
            max-width: 440px;
            padding: 40px;
            background-color: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: 0px; /* Editorial square look */
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            z-index: 1;
            position: relative;
            animation: fadeInUp 0.8s cubic-bezier(0.25, 0.8, 0.25, 1);
        }

        .brand-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .brand-logo {
            font-family: var(--font-serif);
            font-size: 24px;
            font-weight: 500;
            letter-spacing: 6px;
            color: #ffffff;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .brand-subtitle {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: var(--color-primary);
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 24px;
            position: relative;
        }

        .form-label {
            display: block;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--color-text-muted);
            margin-bottom: 8px;
            font-weight: 500;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 14px 16px;
            background-color: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--color-border);
            color: #ffffff;
            font-family: var(--font-sans);
            font-size: 14px;
            font-weight: 300;
            outline: none;
            transition: var(--transition);
        }

        .form-input:focus {
            border-color: var(--color-primary);
            background-color: rgba(255, 255, 255, 0.04);
            box-shadow: 0 0 8px rgba(197, 168, 128, 0.15);
        }

        .error-banner {
            background-color: rgba(224, 108, 117, 0.08);
            border-left: 2px solid var(--color-error);
            padding: 12px 16px;
            margin-bottom: 24px;
            font-size: 13px;
            color: var(--color-error);
            animation: shake 0.4s ease;
        }

        .submit-btn {
            width: 100%;
            padding: 16px;
            background-color: var(--color-primary);
            border: none;
            color: #000000;
            font-family: var(--font-sans);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 3px;
            cursor: pointer;
            transition: var(--transition);
        }

        .submit-btn:hover {
            background-color: var(--color-primary-hover);
            box-shadow: 0 4px 15px rgba(197, 168, 128, 0.25);
        }

        .footer-note {
            text-align: center;
            margin-top: 30px;
            font-size: 11px;
            color: var(--color-text-muted);
            letter-spacing: 1px;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="brand-header">
            <h1 class="brand-logo">Evoraa</h1>
            <p class="brand-subtitle">Administrative suite</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-banner">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" autocomplete="off">
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-wrapper">
                    <input type="email" id="email" name="email" class="form-input" required placeholder="admin@gmail.com">
                </div>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Security Password</label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" class="form-input" required placeholder="••••••••">
                </div>
            </div>

            <button type="submit" class="submit-btn">Authorize Entry</button>
        </form>

        <p class="footer-note">EVORAA CLOTHING SYSTEM v1.0.0</p>
    </div>

</body>
</html>

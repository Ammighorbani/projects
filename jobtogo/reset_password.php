<?php
session_start();
require __DIR__ . '/vendor/autoload.php';
include 'db.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$valid = false;
$message = '';
$message_type = '';

if ($token !== '') {
    $stmt = $conn->prepare("SELECT id, email, username, token_expire FROM users_account WHERE token = ? LIMIT 1");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($user_id, $email, $username, $token_expire);
        $stmt->fetch();

        if (strtotime($token_expire) > time()) {
            $valid = true;
        } else {
            $message = '❌ This password reset link has expired.';
            $message_type = 'error';
        }
    } else {
        $message = '❌ Invalid reset link.';
        $message_type = 'error';
    }
    $stmt->close();
} else {
    $message = '❌ No token provided.';
    $message_type = 'error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$valid) {
        $message = '❌ Invalid or expired reset link.';
        $message_type = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = '❌ Passwords do not match.';
        $message_type = 'error';
    } elseif (strlen($new_password) < 8) {
        $message = '❌ Password must be at least 8 characters long.';
        $message_type = 'error';
    } else {
        $hashed = password_hash($new_password, PASSWORD_BCRYPT);
        $u_stmt = $conn->prepare("UPDATE users_account SET password = ?, token = NULL, token_expire = NULL WHERE token = ?");
        $u_stmt->bind_param('ss', $hashed, $token);
        if ($u_stmt->execute()) {
            $message = '✅ Password successfully reset. You can now log in.';
            $message_type = 'success';
            $valid = false;
        } else {
            $message = '❌ Server error. Please try again.';
            $message_type = 'error';
        }
        $u_stmt->close();
    }
}

// auto-detect your login page path
$login_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? "https://" : "http://") . $_SERVER['HTTP_HOST'] . "/login.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password — JobToGo</title>
<link rel="stylesheet" href="/styles/forget_password.css"> <!-- use same theme as other forms -->
</head>
<body>
<section class="container">
    <div class="circles">
        <div class="circle-1"></div>
        <div class="circle-2"></div>
    </div>

    <section class="log-in">
        <form id="resetForm" method="post" class="log-in-form" novalidate>
            <div class="log-in-title">
                <h2 style="font-size: 40px;">Reset Password</h2>
            </div>

            <?php if ($message !== ''): ?>
                <p id="serverMessage" class="message <?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </p>
            <?php endif; ?>

            <?php if ($valid): ?>
                <div class="log-in-inputs">
                    <div class="username">
                        <label for="new_password" class="Username-lable">New Password</label>
                        <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>
                    </div>
                    <div class="username">
                        <label for="confirm_password" class="Username-lable">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm password" required>
                        <small id="errorMsg" style="display:none; color:#ffb3b3"></small>
                    </div>
                </div>

                <div class="log-in-button">
                    <button id="submitBtn" type="submit" class="log-in-button">Reset Password</button>
                </div>
            <?php endif; ?>

            <div class="need-register-button">
                <a href="<?php echo htmlspecialchars($login_link); ?>">Back to Login</a>
            </div>
        </form>
    </section>
</section>

<script>
document.getElementById('resetForm')?.addEventListener('submit', function(e) {
    const pass = document.getElementById('new_password').value.trim();
    const confirm = document.getElementById('confirm_password').value.trim();
    const err = document.getElementById('errorMsg');
    const btn = document.getElementById('submitBtn');

    err.style.display = 'none';
    if (pass.length < 8) {
        e.preventDefault();
        err.textContent = 'Password must be at least 8 characters long.';
        err.style.display = 'block';
        return false;
    }
    if (pass !== confirm) {
        e.preventDefault();
        err.textContent = 'Passwords do not match.';
        err.style.display = 'block';
        return false;
    }
    btn.disabled = true;
    btn.textContent = 'Updating...';
});
</script>
</body>
</html>

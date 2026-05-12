<?php
// forget_password.php — JobToGo
// REQUIREMENTS: composer require phpmailer/phpmailer

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require __DIR__ . '/vendor/autoload.php'; // PHPMailer autoload
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include 'db.php';         // must define $conn (mysqli)
include 'functions.php';  // must contain generateRandomString() or similar

// ---------- SMTP CONFIG (poste.io) ----------
$SMTP_HOST = 'mail.jobtogo.ir';
$SMTP_PORT = 587;                    // 587 for TLS, 465 for SSL
$SMTP_USER = 'support@jobtogo.ir';
$SMTP_PASS = 'Ammighorbani12';
$SMTP_SECURE = 'tls';                // 'tls' or 'ssl'
$FROM_EMAIL = 'support@jobtogo.ir';
$FROM_NAME  = 'JobToGo Support';

// fallback random generator if not in functions.php
if (!function_exists('generateRandomString')) {
    function generateRandomString($length = 20) {
        return bin2hex(random_bytes(max(8, intval($length/2))));
    }
}

$message = '';
$message_type = ''; // 'success' or 'error'

// ---------- FORM PROCESS ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameOrEmail = isset($_POST['username']) ? trim($_POST['username']) : '';

    if ($usernameOrEmail === '') {
        $message = 'Please enter your username or email.';
        $message_type = 'error';
    } else {
        // Find the user (prepared statement)
        $sql = "SELECT id, email, username FROM users_account WHERE username = ? OR email = ? LIMIT 1";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('ss', $usernameOrEmail, $usernameOrEmail);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                $stmt->bind_result($user_id, $email, $db_username);
                $stmt->fetch();

                // generate secure token and expiration (1 hour)
                $token = generateRandomString(40);
                $expires_at = date('Y-m-d H:i:s', time() + 3600);

                // store token and expiration in DB (add columns token, token_expire if missing)
                $u_stmt = $conn->prepare("UPDATE users_account SET token = ?, token_expire = ? WHERE id = ?");
                if ($u_stmt) {
                    $u_stmt->bind_param('ssi', $token, $expires_at, $user_id);
                    $u_stmt->execute();
                    $u_stmt->close();

                    // build reset link
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                    $host = $_SERVER['HTTP_HOST'];
                    $reset_link = $protocol . '://' . $host . '/reset_password.php?token=' . urlencode($token);

                    // send email via PHPMailer
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host       = $SMTP_HOST;
                        $mail->SMTPAuth   = true;
                        $mail->Username   = $SMTP_USER;
                        $mail->Password   = $SMTP_PASS;
                        $mail->SMTPSecure = $SMTP_SECURE;
                        $mail->Port       = $SMTP_PORT;
                        $mail->SMTPOptions = [
                            'ssl' => [
                                'verify_peer' => false,
                                'verify_peer_name' => false,
                                'allow_self_signed' => true,
                            ],
                        ];

                        $mail->setFrom($FROM_EMAIL, $FROM_NAME);
                        $mail->addAddress($email, $db_username ?: '');

                        $mail->isHTML(true);
                        $mail->Subject = 'JobToGo — Password reset request';
                        $mail->Body = "
                            <p>Hi " . htmlspecialchars($db_username ?: '') . ",</p>
                            <p>We received a request to reset your JobToGo password. Click the link below to reset it (valid for 1 hour):</p>
                            <p><a href='$reset_link'>$reset_link</a></p>
                            <p>If you didn't request a password reset, you can ignore this email.</p>
                            <p>— JobToGo Support</p>
                        ";

                        $mail->send();

                        $message = '✅ A reset link has been sent to your email address.';
                        $message_type = 'success';
                    } catch (Exception $e) {
                        $message = '❌ Could not send email. Mailer error: ' . htmlspecialchars($mail->ErrorInfo);
                        $message_type = 'error';
                    }
                } else {
                    $message = '❌ Server error (cannot store token).';
                    $message_type = 'error';
                }
            } else {
                $message = 'If that account exists, a reset link will be sent to its email.';
                $message_type = 'success';
            }
            $stmt->close();
        } else {
            $message = '❌ Server error. Please try again later.';
            $message_type = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Forgot Password — JobToGo</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="/styles/forget_password.css">
</head>
<body>

<section class="container">
    <div class="circles">
        <div class="circle-1"></div>
        <div class="circle-2"></div>
    </div>

    <section class="log-in">
        <form id="forgotForm" action="forget_password.php" method="post" class="log-in-form" novalidate>
            <div class="log-in-title">
                <h2 style="font-size: 40px;">Forgot Password</h2>
            </div>

            <?php if ($message !== ''): ?>
                <p id="serverMessage" class="message <?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </p>
            <?php endif; ?>

            <div class="log-in-inputs">
                <div class="username">
                    <label for="Username" class="Username-lable">Email</label>
                    <input autocomplete="username" type="text" name="username" id="Username" placeholder="Email" required>
                    <small id="clientError" style="display:none; color:#ffb3b3"></small>
                </div>
            </div>

            <div class="log-in-button">
                <button id="submitBtn" type="submit" class="log-in-button">Send Reset Link</button>
            </div>

            <div class="need-register-button">
                <a href="login.php">Back to Login</a>
            </div>
        </form>
    </section>
</section>

<script>
// Basic client-side validation and UX
document.getElementById('forgotForm').addEventListener('submit', function(e) {
    var input = document.getElementById('Username');
    var err = document.getElementById('clientError');
    var val = input.value.trim();
    if (!val) {
        e.preventDefault();
        err.style.display = 'block';
        err.textContent = 'Please enter your username or email.';
        return false;
    }
    // disable button to prevent double submit
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitBtn').textContent = 'Sending...';
});
</script>

</body>
</html>
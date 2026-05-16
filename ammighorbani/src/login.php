<?php
// Show all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'db.php'; // Make sure $conn is a valid mysqli connection

$message = ""; // Initialize message

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get user input safely
    $username = isset($_POST["username"]) ? trim($_POST["username"]) : '';
    $password = isset($_POST["password"]) ? $_POST["password"] : '';

    if ($username === '' || $password === '') {
        $message = "Please enter both username (or email/phone) and password.";
    } else {

        // Prepare statement to prevent SQL injection
        $sql = "SELECT id, uuid, username, email, phone_number, password 
                FROM users_account 
                WHERE username = ? OR email = ? OR phone_number = ? 
                LIMIT 1";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sss", $username, $username, $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows === 1) {
                // Bind the result to variables
                $stmt->bind_result($id, $db_uuid,$db_username, $db_email, $db_phone, $db_password);
                $stmt->fetch();

                // Check password (hashed or plain text)
                if ((password_verify($password, $db_password)) || ($password === $db_password)) {
                    // Successful login
                    session_regenerate_id(true);
                    $_SESSION['is_logged'] = true;
                    $_SESSION['uuid'] = $db_uuid;
                    $_SESSION['username'] = $db_username;
                    $_SESSION['email'] = $db_email;
                    $_SESSION['phone_number'] = $db_phone;
                    $_SESSION['user_id'] = $id;

                    header("Location: user_panel.php");
                    exit;
                } else {
                    $message = "❌ Invalid password!";
                }
            } else {
                $message = "❌ Username / Email / Phone not found!";
            }

            $stmt->close();
        } else {
            $message = "Server error. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - JobToGo</title>
    <link rel="stylesheet" href="/styles/login.css">
</head>
<body>

<section class="container">
    <div class="circles">
        <div class="circle-1"></div>
        <div class="circle-2"></div>
    </div>

    <section class="log-in">
        <form action="login.php" method="post" class="log-in-form">
            <div class="log-in-title">
                <h1>Login Here</h1>
            </div>

            <!-- Display error message -->
            <?php if (!empty($message)) : ?>
                <p style="color:red; text-align:center;"><?php echo $message; ?></p>
            <?php endif; ?>

            <div class="log-in-inputs">
                <div class="username">
                    <label for="Username" class="Username-lable">Username</label>
                    <input type="text" name="username" id="Username" placeholder="Email or Phone or Username" required>
                </div>

                <div class="password">
                    <label for="Password" class="Password-lable">Password</label>
                    <input type="password" name="password" id="Password" placeholder="Password" required>
                </div>
            </div>

            <div class="log-in-button">
                <button type="submit" class="log-in-button">Log In</button>
            </div>

            <div class="need-register-button">
                <a href="register.php" style="color: white;">Need a registration?</a><br>
            </div>

            </div>

                            <div class="need-forget-password-button">
                    
                <a href='forget_password.php' style="color: white;">forget password?</a><br>
                
            </div>
        </form>
    </section>
</section>


<script>
    // گرفتن پارامترهای URL
    const urlParams = new URLSearchParams(window.location.search);

    // چک کردن اینکه آیا logout=1 وجود دارد یا نه
    if (urlParams.has('logout')) {
        alert("با موفقیت خارج شدید ✔");
    }
</script>


</body>
</html>

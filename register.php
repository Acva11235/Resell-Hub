<?php
session_start();
require_once 'db_connect.php';

$error_message = '';
$success = false;

if (isset($_SESSION['seller_id'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
         $error_message = "Password must be at least 6 characters long.";
    } else {
        $sql_check = "SELECT id FROM sellers WHERE email = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $error_message = "An account with this email already exists.";
            $stmt_check->close();
        } else {
            $stmt_check->close();

            $sql_insert = "INSERT INTO sellers (name, email, password_hash) VALUES (?, ?, ?)";
            $stmt_insert = $conn->prepare($sql_insert);

            if ($stmt_insert) {
                $stmt_insert->bind_param("sss", $name, $email, $password);

                if ($stmt_insert->execute()) {
                    header("Location: login.php?registered=success");
                    exit();
                } else {
                    $error_message = "Registration failed. Please try again later.";
                }
                $stmt_insert->close();
            } else {
                 $error_message = "Database error. Please try again later.";
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Registration - Resell Hub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Poppins:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="auth_style.css"> 
</head>
<body>

    <header class="site-header">
         <div class="container header-container">
            <div class="logo"><a href="index.html" style="color: #2F4F4F; text-decoration: none;">Resell Hub</a></div>
             <nav class="main-nav">
                 <ul>
                     <li><a href="/#how-it-works">How It Works</a></li>
                     <li><a href="/products">Browse Items</a></li>
                 </ul>
             </nav>
            <div class="auth-buttons">
                <a href="login.php" class="btn btn-secondary">Login</a>
            </div>
        </div>
    </header>

    <main class="auth-page">
        <div class="auth-form">
            <h1>Create Seller Account</h1>

            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <form action="register.php" method="post">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password (min 6 characters)</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn btn-accent">Register</button>
            </form>
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </main>

     <footer class="site-footer-bottom" style="position: relative; bottom: 0; width: 100%;">
        <div class="container">
            <p>© <?php echo date("Y"); ?> Resell Hub. All rights reserved.</p>
             <nav class="footer-nav">
                <ul>
                    <li><a href="/about">About</a></li>
                    <li><a href="/terms">Terms</a></li>
                    <li><a href="/privacy">Privacy</a></li>
                </ul>
            </nav>
        </div>
    </footer>

</body>
</html>

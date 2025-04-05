<?php
session_start();
require_once 'db_connect.php';

$error_message = '';
$success_message = '';

if (isset($_SESSION['seller_id'])) {
    header("Location: dashboard.php");
    exit();
}

if (isset($_GET['registered']) && $_GET['registered'] === 'success') {
    $success_message = "Registration successful! Please log in.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error_message = "Please enter both email and password.";
    } else {
        $sql = "SELECT id, name, password_hash FROM sellers WHERE email = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $seller = $result->fetch_assoc();

                if ($password === $seller['password_hash']) {
                    $_SESSION['seller_id'] = $seller['id'];
                    $_SESSION['seller_name'] = $seller['name'];
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error_message = "Invalid email or password.";
                }
            } else {
                $error_message = "Invalid email or password.";
            }
            $stmt->close();
        } else {
            $error_message = "Database error. Please try again later.";
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
    <title>Seller Login - Resell Hub</title>
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
                <a href="register.php" class="btn btn-primary">Register</a>
            </div>
        </div>
    </header>

    <main class="auth-page">
        <div class="auth-form">
            <h1>Seller Login</h1>

            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <form action="login.php" method="post">
                <div class="form-group">
                    <label for="email">

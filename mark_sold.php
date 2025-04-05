<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['seller_id'])) {
    header("Location: login.php?error=auth_required");
    exit();
}

$seller_id = $_SESSION['seller_id'];
$product_id = null;
$redirect_url = 'my_listings.php';

if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $product_id = (int)$_GET['id'];
} else {
    header("Location: " . $redirect_url . "?error=invalid_id");
    exit();
}

if ($product_id > 0) {
    $sql = "UPDATE products SET status = 'sold' WHERE id = ? AND seller_id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ii", $product_id, $seller_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                header("Location: " . $redirect_url . "?status_updated=success_sold");
                exit();
            } else {
                header("Location: " . $redirect_url . "?error=update_failed_or_unauthorized");
                exit();
            }
        } else {
            error_log("Mark Sold Execute Error: " . $stmt->error);
            header("Location: " . $redirect_url . "?error=db_error");
            exit();
        }
        $stmt->close();
    } else {
        error_log("Mark Sold Prepare Error: " . $conn->error);
        header("Location: " . $redirect_url . "?error=db_error");
        exit();
    }
} else {
    header("Location: " . $redirect_url . "?error=invalid_id");
    exit();
}

$conn->close();
header("Location: " . $redirect_url);
exit();
?>

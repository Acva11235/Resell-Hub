<?php
session_start(); 
require_once 'db_connect.php'; 

if (!isset($_SESSION['seller_id'])) {
    header("Location: login.php?error=auth_required"); 
    exit(); 
}

$seller_id = $_SESSION['seller_id'];
$seller_name = isset($_SESSION['seller_name']) ? htmlspecialchars($_SESSION['seller_name']) : 'Seller'; 

$active_listings_count = 0;
$total_inquiries_count = 0;

$sql_listings = "SELECT COUNT(*) as count FROM products WHERE seller_id = ? AND status = 'active'";
$stmt_listings = $conn->prepare($sql_listings);
if ($stmt_listings) {
    $stmt_listings->bind_param("i", $seller_id);
    $stmt_listings->execute();
    $result_listings = $stmt_listings->get_result();
    if ($row = $result_listings->fetch_assoc()) {
        $active_listings_count = $row['count'];
    }
    $stmt_listings->close();
} else {
    
    error_log("Error preparing listing count query: " . $conn->error);
}

$sql_inquiries = "SELECT COUNT(i.id) as count
                  FROM inquiries i
                  JOIN products p ON i.product_id = p.id
                  WHERE p.seller_id = ?";
$stmt_inquiries = $conn->prepare($sql_inquiries);
if ($stmt_inquiries) {
    $stmt_inquiries->bind_param("i", $seller_id);
    $stmt_inquiries->execute();
    $result_inquiries = $stmt_inquiries->get_result();
     if ($row = $result_inquiries->fetch_assoc()) {
        $total_inquiries_count = $row['count'];
    }
    $stmt_inquiries->close();
} else {
     
     error_log("Error preparing inquiry count query: " . $conn->error);
}

$conn->close(); 

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard - Resell Hub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Poppins:wght@500;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="style.css"> 
    <link rel="stylesheet" href="dashboard_style.css"> 
</head>
<body>

    <header class="site-header">
        <div class="container header-container">
            <div class="logo"><a href="index.html" style="color: #2F4F4F; text-decoration: none;">Resell Hub</a></div>
            <nav class="main-nav">
                
                <ul>
                    <li><a href="my_listings.php">My Listings</a></li>
                    <li><a href="view_inquiries.php">Inquiries</a></li>
                    
                    </ul>
            </nav>
            <div class="user-menu">
                <span>Welcome, <?php echo $seller_name; ?></span>
                
                <a href="logout.php" class="btn btn-secondary btn-small-nav">Logout</a>
            </div>
        </div>
    </header>

    <main class="dashboard-page container">
        <h1>Your Dashboard</h1>

        <section class="stats-container">
            <div class="stat-box">
                <h3>Active Listings</h3>
                <p class="stat-number"><?php echo $active_listings_count; ?></p>
                <a href="my_listings.php" class="stat-link">View Listings</a>
            </div>
            <div class="stat-box">
                <h3>Total Inquiries</h3>
                <p class="stat-number"><?php echo $total_inquiries_count; ?></p>
                 <a href="view_inquiries.php" class="stat-link">View Inquiries</a>
            </div>
             <div class="stat-box stat-box-action">
                <h3>Quick Action</h3>
                <a href="list_product.php" class="btn btn-accent">List a New Item</a>
            </div>
        </section>

        <section class="recent-activity">
            <h2>Recent Activity</h2>
            <div class="activity-placeholder">
                <p>Recent inquiries or listing updates will appear here soon.</p>
                
                <?php

                if (!empty($recent_inquiries)) {
                    echo "<ul>";
                    foreach ($recent_inquiries as $inquiry) {
                        echo "<li>";
                        echo "New inquiry from <strong>" . htmlspecialchars($inquiry['buyer_name']) . "</strong>";
                        echo " for '" . htmlspecialchars($inquiry['title']) . "'";
                        echo " on " . date("M d, Y", strtotime($inquiry['created_at']));
                        
                        echo "</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p>No recent inquiries found.</p>";
                }
                
                ?>
            </div>
        </section>

    </main>

    <footer class="site-footer-bottom">
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
<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['seller_id'])) {
    header("Location: login.php?error=auth_required");
    exit();
}

$seller_id = $_SESSION['seller_id'];
$seller_name = isset($_SESSION['seller_name']) ? htmlspecialchars($_SESSION['seller_name']) : 'Seller';

$inquiries = [];
$error_message = '';

$sql = "SELECT
            i.id AS inquiry_id,
            i.buyer_name,
            i.buyer_email,
            i.buyer_phone,
            i.message,
            i.created_at AS inquiry_date,
            p.id AS product_id,
            p.title AS product_title
        FROM
            inquiries i
        JOIN
            products p ON i.product_id = p.id
        WHERE
            p.seller_id = ?
        ORDER BY
            i.created_at DESC";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $seller_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $inquiries[] = $row;
    }
    $stmt->close();
} else {
    $error_message = "Error fetching inquiries: " . $conn->error;
    error_log("View Inquiries Error: " . $conn->error);
}

$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Inquiries - Resell Hub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Poppins:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="view_inquiries_style.css">
</head>
<body>

    <header class="site-header">
        <div class="container header-container">
             <div class="logo"><a href="index.html" style="color: #2F4F4F; text-decoration: none;">Resell Hub</a></div>
             <nav class="main-nav">
                 <ul>
                     <li><a href="dashboard.php">Dashboard</a></li>
                     <li><a href="my_listings.php">My Listings</a></li>
                     <li><a href="view_inquiries.php" class="active">Inquiries</a></li>
                 </ul>
             </nav>
             <div class="user-menu">
                 <span>Welcome, <?php echo $seller_name; ?></span>
                 <a href="logout.php" class="btn btn-secondary btn-small-nav">Logout</a>
             </div>
        </div>
    </header>

    <main class="view-inquiries-page container">
        <h1>View Inquiries</h1>
        <p>Here are the inquiries received for your listed products. Contact buyers directly using the information provided.</p>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if (empty($inquiries) && empty($error_message)): ?>
            <div class="no-inquiries">
                <p>You haven't received any inquiries yet.</p>
            </div>
        <?php elseif (!empty($inquiries)): ?>

            <div class="inquiries-table-container">
                <table class="inquiries-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Buyer Name</th>
                            <th>Contact Info</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inquiries as $inquiry): ?>
                        <tr>
                            <td data-label="Date" class="inquiry-date">
                                <?php echo date("M d, Y", strtotime($inquiry['inquiry_date'])); ?><br>
                                <small><?php echo date("h:i A", strtotime($inquiry['inquiry_date'])); ?></small>
                            </td>
                            <td data-label="Product" class="inquiry-product">
                                <?php echo htmlspecialchars($inquiry['product_title']); ?>
                            </td>
                            <td data-label="Buyer Name">
                                <?php echo htmlspecialchars($inquiry['buyer_name']); ?>
                            </td>
                            <td data-label="Contact Info" class="inquiry-contact">
                                <?php if (!empty($inquiry['buyer_email'])): ?>
                                    <div>Email: <a href="mailto:<?php echo htmlspecialchars($inquiry['buyer_email']); ?>"><?php echo htmlspecialchars($inquiry['buyer_email']); ?></a></div>
                                <?php endif; ?>
                                <?php if (!empty($inquiry['buyer_phone'])): ?>
                                    <div>Phone: <?php echo htmlspecialchars($inquiry['buyer_phone']); ?></div>
                                <?php endif; ?>
                                <?php if (empty($inquiry['buyer_email']) && empty($inquiry['buyer_phone'])): ?>
                                    <span class="no-contact">No contact info provided</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Message" class="inquiry-message">
                                <?php echo !empty($inquiry['message']) ? nl2br(htmlspecialchars($inquiry['message'])) : '<span class="no-message">(No message)</span>'; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

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

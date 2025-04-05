<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['seller_id'])) {
    header("Location: login.php?error=auth_required");
    exit();
}

$seller_id = $_SESSION['seller_id'];
$seller_name = isset($_SESSION['seller_name']) ? htmlspecialchars($_SESSION['seller_name']) : 'Seller';

$listings = [];
$error_message = '';

$sql = "SELECT
            p.id,
            p.title,
            p.price,
            p.status,
            p.created_at,
            MIN(pi.image_path) as image_path,
            COUNT(DISTINCT i.id) as inquiry_count
        FROM
            products p
        LEFT JOIN
            product_images pi ON p.id = pi.product_id
        LEFT JOIN
            inquiries i ON p.id = i.product_id
        WHERE
            p.seller_id = ?
        GROUP BY
            p.id
        ORDER BY
            p.created_at DESC";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $seller_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $listings[] = $row;
    }
    $stmt->close();
} else {
    $error_message = "Error fetching listings: " . $conn->error;
    error_log("My Listings Error: " . $conn->error);
}

$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Listings - Resell Hub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Poppins:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="my_listings_style.css">
</head>
<body>

    <header class="site-header">
        <div class="container header-container">
             <div class="logo"><a href="dashboard.php" style="color: #2F4F4F; text-decoration: none;">Resell Hub</a></div>
             <nav class="main-nav">
                 <ul>
                     <li><a href="dashboard.php">Dashboard</a></li>
                     <li><a href="my_listings.php" class="active">My Listings</a></li>
                     <li><a href="view_inquiries.php">Inquiries</a></li>
                 </ul>
             </nav>
             <div class="user-menu">
                 <span>Welcome, <?php echo $seller_name; ?></span>
                 <a href="logout.php" class="btn btn-secondary btn-small-nav">Logout</a>
             </div>
        </div>
    </header>

    <main class="my-listings-page container">
        <div class="page-header">
            <h1>My Listings</h1>
            <a href="list_product.php" class="btn btn-accent">List New Item</a>
        </div>

        <?php if (!empty($feedback_message)): ?>
            <div class="feedback-message <?php echo $feedback_type; ?>">
                <?php echo htmlspecialchars($feedback_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if (empty($listings) && empty($error_message)): ?>
            <div class="no-listings">
                <p>You haven't listed any items yet.</p>
                <a href="list_product.php" class="btn btn-primary">List Your First Item</a>
            </div>
        <?php elseif (!empty($listings)): ?>
            <div class="listings-table-container">
                <table class="listings-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Inquiries</th>
                            <th>Date Listed</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listings as $listing): ?>
                        <tr>
                            <td class="listing-image">
                                <?php if (!empty($listing['image_path']) && file_exists($listing['image_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($listing['image_path']); ?>" alt="Product Image">
                                <?php else: ?>
                                    <img src="placeholder_image.png" alt="No image available">
                                <?php endif; ?>
                            </td>
                            <td data-label="Title"><?php echo htmlspecialchars($listing['title']); ?></td>
                            <td data-label="Price">$<?php echo number_format($listing['price'], 2); ?></td>
                            <td data-label="Status">
                                <span class="status-badge status-<?php echo strtolower(htmlspecialchars($listing['status'])); ?>">
                                    <?php echo ucfirst(htmlspecialchars($listing['status'])); ?>
                                </span>
                            </td>
                             <td data-label="Inquiries"><?php echo $listing['inquiry_count']; ?></td>
                             <td data-label="Date Listed"><?php echo date("M d, Y", strtotime($listing['created_at'])); ?></td>
                            <td data-label="Actions" class="listing-actions">
                                <a href="edit_listing.php?id=<?php echo $listing['id']; ?>" class="btn-action btn-edit" title="Edit">Edit</a>
                                <?php if ($listing['status'] === 'active'): ?>
                                    <a href="mark_sold.php?id=<?php echo $listing['id']; ?>" class="btn-action btn-sold" title="Mark as Sold" onclick="return confirm('Mark this item as sold?');">Sold</a>
                                <?php elseif ($listing['status'] === 'sold'): ?>
                                    <a href="mark_active.php?id=<?php echo $listing['id']; ?>" class="btn-action btn-activate" title="Mark as Active" onclick="return confirm('Mark this item as active again?');">Activate</a>
                                <?php endif; ?>
                                <a href="delete_listing.php?id=<?php echo $listing['id']; ?>" class="btn-action btn-delete" title="Delete" onclick="return confirm('Are you sure you want to delete this listing permanently?');">Delete</a>
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

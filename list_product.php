<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['seller_id'])) {
    header("Location: login.php?error=auth_required");
    exit();
}

$seller_id = $_SESSION['seller_id'];
$seller_name = isset($_SESSION['seller_name']) ? htmlspecialchars($_SESSION['seller_name']) : 'Seller';

$categories = []; // Initialize empty array for categories
$categories_xml_file = 'categories.xml';

if (file_exists($categories_xml_file)) {
    $xml = simplexml_load_file($categories_xml_file);
    if ($xml !== false) {
        foreach ($xml->category as $cat_element) {
            $categories[] = (string) $cat_element->name; 
        }
    } else {
        error_log("Failed to parse categories.xml");
    }
} else {
     error_log("categories.xml not found at: " . $categories_xml_file);
}

$error_message = '';
$success_message = '';
$form_data = [
    'title' => '',
    'description' => '',
    'price' => '',
    'category' => '',
    'condition' => ''
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $form_data = [
        'title' => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'price' => trim($_POST['price'] ?? ''),
        'category' => trim($_POST['category'] ?? ''),
        'condition' => trim($_POST['condition'] ?? '')
    ];

    $errors = [];
    if (empty($form_data['title'])) $errors[] = "Product Title is required.";
    if (empty($form_data['description'])) $errors[] = "Description is required.";
    if (empty($form_data['price'])) $errors[] = "Price is required.";
    if (!is_numeric($form_data['price']) || $form_data['price'] < 0) $errors[] = "Price must be a valid positive number.";
    if (empty($form_data['category'])) $errors[] = "Category is required.";
    if (empty($form_data['condition'])) $errors[] = "Condition is required.";

    $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_file_size = 5 * 1024 * 1024;
    $uploaded_images = [];

    if (isset($_FILES['product_images']) && !empty($_FILES['product_images']['name'][0])) {
        $image_files = $_FILES['product_images'];
        $num_files = count($image_files['name']);

        for ($i = 0; $i < $num_files; $i++) {
            if ($image_files['error'][$i] === UPLOAD_ERR_OK) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime_type = $finfo->file($image_files['tmp_name'][$i]);
                if (!in_array($mime_type, $allowed_mime_types)) {
                    $errors[] = "File '" . htmlspecialchars($image_files['name'][$i]) . "' has an invalid type (only JPG, PNG, GIF, WEBP allowed).";
                    continue;
                }
                if ($image_files['size'][$i] > $max_file_size) {
                    $errors[] = "File '" . htmlspecialchars($image_files['name'][$i]) . "' is too large (max 5MB).";
                    continue;
                }
                $uploaded_images[] = $i;
            } elseif ($image_files['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                $errors[] = "Error uploading file '" . htmlspecialchars($image_files['name'][$i]) . "'. Error code: " . $image_files['error'][$i];
            }
        }
        if(empty($uploaded_images)){
            $errors[] = "Please upload at least one valid image (JPG, PNG, GIF, WEBP, max 5MB).";
        }
    } else {
        $errors[] = "At least one product image is required.";
    }

    if (empty($errors)) {
        $conn->begin_transaction();

        try {
            $sql_product = "INSERT INTO products (seller_id, title, description, price, category, `condition`, status) VALUES (?, ?, ?, ?, ?, ?, 'active')";
            $stmt_product = $conn->prepare($sql_product);
            if (!$stmt_product) throw new Exception("Prepare failed (product): " . $conn->error);

            $stmt_product->bind_param("issdss",
                $seller_id,
                $form_data['title'],
                $form_data['description'],
                $form_data['price'],
                $form_data['category'],
                $form_data['condition']
            );

            if (!$stmt_product->execute()) throw new Exception("Execute failed (product): " . $stmt_product->error);

            $product_id = $conn->insert_id;
            $stmt_product->close();

            $upload_dir = 'uploads/product_images/';
            $sql_image = "INSERT INTO product_images (product_id, image_path) VALUES (?, ?)";
            $stmt_image = $conn->prepare($sql_image);
            if (!$stmt_image) throw new Exception("Prepare failed (image): " . $conn->error);

            $image_paths_for_db = [];

            foreach ($uploaded_images as $index) {
                $tmp_name = $image_files['tmp_name'][$index];
                $original_name = $image_files['name'][$index];
                $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
                $new_filename = uniqid('prod_' . $product_id . '_', true) . '.' . strtolower($file_extension);
                $destination_path = $upload_dir . $new_filename;
                $relative_path = $destination_path;

                if (move_uploaded_file($tmp_name, $destination_path)) {
                    $stmt_image->bind_param("is", $product_id, $relative_path);
                    if (!$stmt_image->execute()) {
                        unlink($destination_path);
                        throw new Exception("Execute failed (image insert): " . $stmt_image->error);
                    }
                    $image_paths_for_db[] = $relative_path;
                } else {
                    throw new Exception("Failed to move uploaded file: " . htmlspecialchars($original_name));
                }
            }
            $stmt_image->close();

            $conn->commit();
            $success_message = "Product listed successfully!";
            $form_data = array_fill_keys(array_keys($form_data), '');
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Failed to list product: " . $e->getMessage();
            foreach ($image_paths_for_db as $path) {
                 if (file_exists($path)) unlink($path);
            }
            error_log("Product Listing Error: " . $e->getMessage());
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List New Item - Resell Hub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Poppins:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="list_product_style.css">
</head>
<body>

    <header class="site-header">
        <div class="container header-container">
             <div class="logo"><a href="dashboard.php" style="color: #2F4F4F; text-decoration: none;">Resell Hub</a></div>
             <nav class="main-nav">
                 <ul>
                     <li><a href="dashboard.php">Dashboard</a></li>
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

    <main class="list-product-page container">
        <h1>List a New Item</h1>
        <p>Fill in the details below to list your product.</p>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <form action="list_product.php" method="post" enctype="multipart/form-data" class="list-product-form" novalidate>
            <div class="form-section">
                <h2>Product Details</h2>
                <div class="form-group">
                    <label for="title">Product Title*</label>
                    <input type="text" id="title" name="title" required maxlength="200" value="<?php echo htmlspecialchars($form_data['title']); ?>">
                </div>

                <div class="form-group">
                    <label for="description">Description*</label>
                    <textarea id="description" name="description" rows="6" required><?php echo htmlspecialchars($form_data['description']); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group form-group-half">
                        <label for="price">Price ($)*</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" required value="<?php echo htmlspecialchars($form_data['price']); ?>">
                    </div>
                    <div class="form-group form-group-half">
                    <label for="category">Category*</label>
                        <select id="category" name="category" required>
                            <option value="">-- Select Category --</option>
                            <?php
                            if (!empty($categories)) {
                                foreach ($categories as $category_name) {
                                    $selected = ($form_data['category'] == $category_name) ? 'selected' : '';
                                    echo "<option value=\"" . htmlspecialchars($category_name) . "\" $selected>"
                                         . htmlspecialchars($category_name)
                                         . "</option>";
                                }
                            } else {
                                echo "<option value='' disabled>Error loading categories</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>

                 <div class="form-group">
                    <label for="condition">Condition*</label>
                    <select id="condition" name="condition" required>
                        <option value="" <?php echo ($form_data['condition'] == '') ? 'selected' : ''; ?>>-- Select Condition --</option>
                        <option value="New" <?php echo ($form_data['condition'] == 'New') ? 'selected' : ''; ?>>New</option>
                        <option value="Used - Like New" <?php echo ($form_data['condition'] == 'Used - Like New') ? 'selected' : ''; ?>>Used - Like New</option>
                        <option value="Used - Good" <?php echo ($form_data['condition'] == 'Used - Good') ? 'selected' : ''; ?>>Used - Good</option>
                        <option value="Used - Fair" <?php echo ($form_data['condition'] == 'Used - Fair') ? 'selected' : ''; ?>>Used - Fair</option>
                    </select>
                 </div>
            </div>

            <div class="form-section">
                <h2>Product Images*</h2>
                <div class="form-group">
                    <label for="product_images">Upload Images (JPG, PNG, GIF, WEBP - Max 5MB each)</label>
                    <input type="file" id="product_images" name="product_images[]" multiple accept="image/jpeg, image/png, image/gif, image/webp">
                    <div id="image-preview" class="image-preview-container">
                        <span class="preview-placeholder">Image previews will appear here</span>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                 <button type="submit" class="btn btn-accent btn-large">List Product</button>
                 <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
            </div>

        </form>

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

    <script src="list_product.js"></script>

</body>
</html>

<?php
ob_start(); // Start output buffering to handle session_start() in sidebar
?>
<link rel="stylesheet" href="../../Include/sidebar.css">
<?php include '../../Include/SidebarWarehouse.php'; ?>

<?php
include '../../include/connect-db.php'; // Database connection
$admin_id = isset($user_id) ? $user_id : 65;
// Ensure upload directory exists
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Fetch distinct categories for suggestions
$categoryQuery = "SELECT DISTINCT category FROM products ORDER BY category";
$categoryResult = $conn->query($categoryQuery);
$categories = [];
if ($categoryResult) {
    while ($row = $categoryResult->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}

// Fetch distinct units for suggestions
$unitQuery = "SELECT DISTINCT unit FROM products ORDER BY unit";
$unitResult = $conn->query($unitQuery);
$units = ['kg', 'units']; // Default units
if ($unitResult && $unitResult->num_rows > 0) {
    $units = [];
    while ($row = $unitResult->fetch_assoc()) {
        $units[] = $row['unit'];
    }
}

// Handle file upload for new product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addProduct'])) {
    $productName = $conn->real_escape_string(trim($_POST['productName'] ?? ''));
    $category = $conn->real_escape_string(trim($_POST['category'] ?? ''));
    $price = floatval($_POST['price'] ?? 0);
    $unit = $conn->real_escape_string(trim($_POST['unit'] ?? ''));
    $specialInstructions = $conn->real_escape_string(trim($_POST['specialInstructions'] ?? ''));
    $fileName = $_FILES['imageFile']['name'] ?? '';
    $error = '';

    // Validate inputs
    if (empty($productName) || empty($category) || $price < 0 || empty($unit)) {
        $error = 'Please fill all required fields correctly. Price must be non-negative.';
    } else {
        // Check for duplicate product name
        $query = "SELECT product_id FROM products WHERE name = '$productName'";
        $result = $conn->query($query);
        if ($result && $result->num_rows > 0) {
            $error = 'Product name already exists. Please choose a unique name.';
        }
    }

    if (!$error && !empty($fileName)) {
        $targetFilePath = $uploadDir . time() . '_' . basename($fileName);
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

        // Validate file type and size
        if (!in_array($fileType, $allowedTypes)) {
            $error = 'Only JPG, JPEG, PNG, and GIF files are allowed.';
        } elseif ($_FILES['imageFile']['size'] > 5000000) { // 5MB limit
            $error = 'File size exceeds 5MB limit.';
        } elseif (!move_uploaded_file($_FILES['imageFile']['tmp_name'], $targetFilePath)) {
            $error = 'Failed to upload file. Please check directory permissions.';
        }
    }

    if (!$error) {
        // Convert absolute path to relative path for database storage
        $imagePath = !empty($fileName) ? 'uploads/' . time() . '_' . basename($fileName) : null;
        $specialInstructions = $specialInstructions ?: null;

        // Insert into database
        $query = "INSERT INTO products (name, category, price, unit, img_url, special_instructions, created_at, updated_at) 
                  VALUES ('$productName', '$category', $price, '$unit', " .
            ($imagePath ? "'$imagePath'" : 'NULL') . ", " .
            ($specialInstructions ? "'$specialInstructions'" : 'NULL') . ", NOW(), NOW())";

        if ($conn->query($query)) {
            echo "<script>alert('Product added successfully!'); window.location.href='product-list.php';</script>";
        } else {
            $error = 'Database error: ' . $conn->error;
        }
    }

    if ($error) {
        echo "<script>alert('Error: $error');</script>";
    }
}

// Handle file upload for editing product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editProduct'])) {
    $productId = intval($_POST['editProductId'] ?? 0);
    $productName = $conn->real_escape_string(trim($_POST['editProductName'] ?? ''));
    $category = $conn->real_escape_string(trim($_POST['editCategory'] ?? ''));
    $price = floatval($_POST['editPrice'] ?? 0);
    $unit = $conn->real_escape_string(trim($_POST['editUnit'] ?? ''));
    $specialInstructions = $conn->real_escape_string(trim($_POST['editSpecialInstructions'] ?? ''));
    $fileName = $_FILES['editImageFile']['name'] ?? '';
    $error = '';

    // Validate inputs
    if (empty($productName) || empty($category) || $price < 0 || empty($unit)) {
        $error = 'Please fill all required fields correctly. Price must be non-negative.';
    } else {
        // Check for duplicate product name (excluding current product)
        $query = "SELECT product_id FROM products WHERE name = '$productName' AND product_id != $productId";
        $result = $conn->query($query);
        if ($result && $result->num_rows > 0) {
            $error = 'Product name already exists. Please choose a unique name.';
        }
    }

    if (!$error && !empty($fileName)) {
        $targetFilePath = $uploadDir . time() . '_' . basename($fileName);
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

        // Validate file type and size
        if (!in_array($fileType, $allowedTypes)) {
            $error = 'Only JPG, JPEG, PNG, and GIF files are allowed.';
        } elseif ($_FILES['editImageFile']['size'] > 5000000) { // 5MB limit
            $error = 'File size exceeds 5MB limit.';
        } elseif (!move_uploaded_file($_FILES['editImageFile']['tmp_name'], $targetFilePath)) {
            $error = 'Failed to upload file. Please check directory permissions.';
        }
    }

    if (!$error) {
        // Get existing image path if no new file is uploaded
        $query = "SELECT img_url FROM products WHERE product_id = $productId";
        $result = $conn->query($query);
        $existingProduct = $result->fetch_assoc();
        $imagePath = !empty($fileName) ? 'uploads/' . time() . '_' . basename($fileName) : $existingProduct['img_url'];
        $specialInstructions = $specialInstructions ?: null;

        // Update database
        $query = "UPDATE products SET 
                  name = '$productName', 
                  category = '$category', 
                  price = $price, 
                  unit = '$unit', 
                  img_url = " . ($imagePath ? "'$imagePath'" : 'NULL') . ", 
                  special_instructions = " . ($specialInstructions ? "'$specialInstructions'" : 'NULL') . ", 
                  updated_at = NOW() 
                  WHERE product_id = $productId";

        if ($conn->query($query)) {
            echo "<script>alert('Product updated successfully!'); window.location.href='product-list.php';</script>";
        } else {
            $error = 'Database error: ' . $conn->error;
        }
    }

    if ($error) {
        echo "<script>alert('Error: $error');</script>";
    }
}

// Handle product deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteProduct'])) {
    $productId = intval($_POST['productId'] ?? 0);

    // Optionally, delete the image file from the server
    $query = "SELECT img_url FROM products WHERE product_id = $productId";
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        $product = $result->fetch_assoc();
        if ($product['img_url'] && file_exists(__DIR__ . '/' . $product['img_url'])) {
            unlink(__DIR__ . '/' . $product['img_url']);
        }
    }

    $query = "DELETE FROM products WHERE product_id = $productId";
    if ($conn->query($query)) {
        echo "<script>alert('Product deleted successfully!'); window.location.href='product-list.php';</script>";
    } else {
        echo "<script>alert('Error deleting product: " . $conn->error . "');</script>";
    }
}

// Fetch products from database
$query = "SELECT * FROM products ORDER BY created_at DESC";
$result = $conn->query($query);
$products = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
} else {
    echo "<script>alert('Error fetching products: " . $conn->error . "');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    :root {
        --primary-color: #16a34a;
        --primary-dark: #15803d;
        --primary-light: #22c55e;
        --danger-color: #dc2626;
        --danger-dark: #b91c1c;
        --warning-color: #f59e0b;
        --text-primary: #1f2937;
        --text-secondary: #6b7280;
        --border-color: #e5e7eb;
        --bg-light: #f9fafb;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    }

    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        background: linear-gradient(135deg, #f5f7fa 0%, #e8f5e9 100%);
        color: var(--text-primary);
        line-height: 1.6;
        min-height: 100vh;
        padding: 20px;
    }

    .container {
        max-width: 1400px;
        margin: 0 auto;
    }

    h1 {
        font-size: 32px;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 10px;
        background: white;
        padding: 30px;
        border-radius: 16px;
        box-shadow: var(--shadow-md);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }

    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-family: 'Inter', sans-serif;
        text-decoration: none;
        display: inline-block;
    }

    button[onclick="openModal()"] {
        background: var(--primary-color);
        color: white;
        box-shadow: 0 4px 12px rgba(22, 163, 74, 0.3);
    }

    button[onclick="openModal()"]:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(22, 163, 74, 0.4);
    }

    button[onclick^="openEditModal"] {
        background: var(--warning-color);
        color: white;
        padding: 8px 16px;
        font-size: 13px;
    }

    button[onclick^="openEditModal"]:hover {
        background: #d97706;
        transform: translateY(-1px);
    }

    button[name="deleteProduct"] {
        background: var(--danger-color);
        color: white;
        padding: 8px 16px;
        font-size: 13px;
    }

    button[name="deleteProduct"]:hover {
        background: var(--danger-dark);
        transform: translateY(-1px);
    }

    table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: var(--shadow-md);
        margin-top: 20px;
    }

    thead {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
    }

    thead th {
        padding: 18px 20px;
        text-align: left;
        font-weight: 600;
        font-size: 14px;
        color: white;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    tbody tr {
        border-bottom: 1px solid var(--border-color);
        transition: all 0.2s ease;
    }

    tbody tr:hover {
        background: var(--bg-light);
    }

    tbody td {
        padding: 16px 20px;
        font-size: 14px;
        color: var(--text-primary);
    }

    tbody td img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 10px;
        box-shadow: var(--shadow-sm);
    }

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modal-content {
        background: white;
        margin: 3% auto;
        padding: 0;
        border-radius: 20px;
        width: 90%;
        max-width: 600px;
        box-shadow: var(--shadow-xl);
        animation: slideUp 0.4s ease;
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-content h2 {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
        color: white;
        padding: 24px 32px;
        border-radius: 20px 20px 0 0;
        margin: 0;
        font-size: 24px;
        font-weight: 700;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .close {
        color: white;
        font-size: 32px;
        font-weight: 300;
        cursor: pointer;
        transition: all 0.2s ease;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    .close:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: rotate(90deg);
    }

    .modal-content form {
        padding: 32px;
    }

    label {
        display: block;
        margin-bottom: 8px;
        margin-top: 16px;
        font-weight: 600;
        color: var(--text-primary);
        font-size: 14px;
    }

    label:first-of-type {
        margin-top: 0;
    }

    input[type="text"],
    input[type="number"],
    input[type="file"],
    textarea {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid var(--border-color);
        border-radius: 10px;
        font-size: 15px;
        font-family: 'Inter', sans-serif;
        transition: all 0.3s ease;
        background: white;
        margin-bottom: 8px;
    }

    input[type="text"]:focus,
    input[type="number"]:focus,
    textarea:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
    }

    textarea {
        resize: vertical;
        min-height: 100px;
    }

    button[type="submit"] {
        background: var(--primary-color);
        color: white;
        margin-top: 16px;
    }

    button[type="submit"]:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        h1 {
            flex-direction: column;
            text-align: center;
            font-size: 24px;
        }

        table {
            font-size: 12px;
        }

        thead th,
        tbody td {
            padding: 12px 8px;
        }

        .modal-content {
            width: 95%;
            margin: 10% auto;
        }
    }

    ::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }

    ::-webkit-scrollbar-track {
        background: var(--bg-light);
    }

    ::-webkit-scrollbar-thumb {
        background: var(--primary-color);
        border-radius: 5px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: var(--primary-dark);
    }
    </style>
</head>

<body>
    <div class="container">
        <h1>
            Product List
            <button class="btn" onclick="openModal()">Add Product</button>
        </h1>

        <table border="1">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price (BDT)</th>
                    <th>Unit</th>
                    <th>Image</th>
                    <th>Special Instructions</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($products) > 0): ?>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><?php echo htmlspecialchars($product['category']); ?></td>
                    <td><?php echo number_format($product['price'], 2); ?></td>
                    <td><?php echo htmlspecialchars($product['unit']); ?></td>
                    <td>
                        <?php if ($product['img_url'] && file_exists(__DIR__ . '/' . $product['img_url'])): ?>
                        <img src="<?php echo htmlspecialchars($product['img_url']); ?>" alt="">
                        <?php else: ?>
                        —
                        <?php endif; ?>
                    </td>
                    <td><?php echo $product['special_instructions'] ? htmlspecialchars($product['special_instructions']) : '—'; ?>
                    </td>
                    <td><?php echo $product['created_at']; ?></td>
                    <td><?php echo $product['updated_at']; ?></td>
                    <td>
                        <button class="btn" onclick='openEditModal(<?php echo json_encode($product); ?>)'>Edit</button>
                        <form method="POST" style="display: inline;"
                            onsubmit="return confirm('Are you sure you want to delete this product?');">
                            <input type="hidden" name="productId" value="<?php echo $product['product_id']; ?>">
                            <button type="submit" name="deleteProduct" class="btn">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr>
                    <td colspan="9" style="text-align: center;">No products found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <p><strong>Total Products:</strong> <?php echo count($products); ?></p>
    </div>

    <!-- Add Product Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <h2>
                Add New Product
                <span class="close" onclick="closeModal()">&times;</span>
            </h2>
            <form method="POST" enctype="multipart/form-data">
                <label>Product Name:</label>
                <input type="text" name="productName" required>

                <label>Category:</label>
                <input type="text" name="category" list="categories" required>
                <datalist id="categories">
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat); ?>">
                        <?php endforeach; ?>
                </datalist>

                <label>Price (BDT):</label>
                <input type="number" name="price" step="0.01" min="0" required>

                <label>Unit:</label>
                <input type="text" name="unit" list="units" required>
                <datalist id="units">
                    <?php foreach ($units as $unit): ?>
                    <option value="<?php echo htmlspecialchars($unit); ?>">
                        <?php endforeach; ?>
                </datalist>

                <label>Image:</label>
                <input type="file" name="imageFile" accept="image/*">

                <label>Special Instructions:</label>
                <textarea name="specialInstructions"></textarea>

                <button type="submit" name="addProduct" class="btn">Add Product</button>
            </form>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h2>
                Edit Product
                <span class="close" onclick="closeEditModal()">&times;</span>
            </h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="editProductId" id="editProductId">

                <label>Product Name:</label>
                <input type="text" name="editProductName" id="editProductName" required>

                <label>Category:</label>
                <input type="text" name="editCategory" id="editCategory" list="categories" required>

                <label>Price (BDT):</label>
                <input type="number" name="editPrice" id="editPrice" step="0.01" min="0" required>

                <label>Unit:</label>
                <input type="text" name="editUnit" id="editUnit" list="units" required>

                <label>Image (leave empty to keep current):</label>
                <input type="file" name="editImageFile" accept="image/*">

                <label>Special Instructions:</label>
                <textarea name="editSpecialInstructions" id="editSpecialInstructions"></textarea>

                <button type="submit" name="editProduct" class="btn">Update Product</button>
            </form>
        </div>
    </div>

    <script>
    function openModal() {
        document.getElementById('productModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('productModal').style.display = 'none';
    }

    function openEditModal(product) {
        document.getElementById('editProductId').value = product.product_id;
        document.getElementById('editProductName').value = product.name;
        document.getElementById('editCategory').value = product.category;
        document.getElementById('editPrice').value = product.price;
        document.getElementById('editUnit').value = product.unit;
        document.getElementById('editSpecialInstructions').value = product.special_instructions || '';
        document.getElementById('editModal').style.display = 'block';
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
    </script>
</body>

</html>
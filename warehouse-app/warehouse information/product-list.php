<?php
ob_start(); // Start output buffering to handle session_start() in sidebar
?>
<link rel="stylesheet" href="../../Include/sidebar.css">
<?php include '../../Include/SidebarWarehouse.php'; ?>

<?php
include '../../include/connect-db.php'; // Database connection

$admin_id = isset($user_id) ? $user_id : 65;

// Define the uploads directory path
$uploadDir = __DIR__ . '/uploads/';

// Create uploads directory if it doesn't exist
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0775, true)) {
        die('Failed to create uploads directory. Please check permissions.');
    }
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
                  VALUES ('$productName', '$category', $price, '$unit', " . ($imagePath ? "'$imagePath'" : 'NULL') . ", " . ($specialInstructions ? "'$specialInstructions'" : 'NULL') . ", NOW(), NOW())";
        if ($conn->query($query)) {
            echo "<script>alert('Product added successfully!'); window.location.href='product-list.php';</script>";
        } else {
            $error = 'Database error: ' . $conn->error;
        }
    }

    if ($error) {
        echo "<script>alert('$error');</script>";
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
        $query = "UPDATE products
                  SET name = '$productName', category = '$category', price = $price, unit = '$unit',
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
        echo "<script>alert('$error');</script>";
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
        echo "<script>alert('Database error: " . addslashes($conn->error) . "');</script>";
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
    echo "<script>alert('Error fetching products: " . addslashes($conn->error) . "');</script>";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product List</title>
    <link rel="stylesheet" href="product-list.css">
</head>

<body>
    <div class="container">
        <h1>Product List</h1>

        <div class="controls">
            <input type="text" id="searchInput" placeholder="Search Products..." />
            <div class="filter-container">
                <label for="filterDropdown">Filter by:</label>
                <div class="dropdown">
                    <button class="dropdown-toggle" onclick="toggleDropdown()">All Products ▾</button>
                    <div class="dropdown-menu" id="dropdownMenu">
                        <div class="group">
                            <span class="group-title">Category</span>
                            <label><input type="checkbox" class="filter-option" value="all"> all</label>
                            <?php foreach ($categories as $cat): ?>
                                <label><input type="checkbox" class="filter-option" value="<?php echo htmlspecialchars($cat); ?>"> <?php echo htmlspecialchars($cat); ?></label>
                            <?php endforeach; ?>
                        </div>
                        <div class="group">
                            <span class="group-title">Unit</span>
                            <label><input type="checkbox" class="filter-option" value="all"> all</label>
                            <?php foreach ($units as $unit): ?>
                                <label><input type="checkbox" class="filter-option" value="<?php echo htmlspecialchars($unit); ?>"> <?php echo htmlspecialchars($unit); ?></label>
                            <?php endforeach; ?>
                        </div>
                        <div class="dropdown-footer">
                            <button type="button" onclick="selectAll()">Select all</button>
                            <button class="apply-btn" onclick="applyFilters()">Apply</button>
                        </div>
                    </div>
                </div>
            </div>
            <button id="addProductBtn">Add Product</button>
        </div>

        <div id="successMessage" class="success-message" style="display: none;">
            <span id="successText"></span>
        </div>

        <div id="productContainer" class="list-view">
            <table id="productTable">
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
                <tbody id="productTableBody">
                    <?php foreach ($products as $product): ?>
                        <tr data-id="<?php echo htmlspecialchars($product['product_id']); ?>">
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                            <td><?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo htmlspecialchars($product['unit']); ?></td>
                            <td>
                                <?php if ($product['img_url']): ?>
                                    <img src="<?php echo htmlspecialchars("../../" . $product['img_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 50px; height: auto;">
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($product['special_instructions'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($product['created_at']); ?></td>
                            <td><?php echo htmlspecialchars($product['updated_at']); ?></td>
                            <td>
                                <button class='action-btn edit-btn' onclick='editProduct(<?php echo $product['product_id']; ?>)'>✎</button>
                                <button class='action-btn delete-btn' onclick='deleteProduct(<?php echo $product['product_id']; ?>)'>🗑</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot id="totalRow" style="display: none;">
                    <tr>
                        <td colspan="4">
                            <span class="label">TotalProducts:</span>
                            <span class="value" id="totalProductsCount">0</span>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="pagination">
            <button id="prevBtn" onclick="changePage(-1)">Previous</button>
            <div id="pageNumbers"></div>
            <button id="nextBtn" onclick="changePage(1)">Next</button>
            <select id="rowsPerPage" onchange="changeRowsPerPage()">
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
            <span>Rows per page</span>
        </div>

        <div id="addProductPopup" class="popup">
            <div class="popup-content">
                <span class="close-btn" onclick="closePopup()">&times;</span>
                <h2>Add New Product</h2>
                <form id="addProductForm" enctype="multipart/form-data" method="post">
                    <input type="hidden" name="addProduct" value="1">
                    <label for="productName">Name:</label>
                    <input type="text" id="productName" name="productName" required>

                    <label for="category">Category:</label>
                    <input type="text" id="category" name="category" list="categoryList" required>
                    <datalist id="categoryList">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>">
                            <?php endforeach; ?>
                    </datalist>

                    <label for="price">Price (BDT):</label>
                    <input type="number" id="price" name="price" min="0" step="0.01" required>

                    <label for="unit">Unit:</label>
                    <select id="unit" name="unit" required>
                        <?php foreach ($units as $unit): ?>
                            <option value="<?php echo htmlspecialchars($unit); ?>"><?php echo htmlspecialchars($unit); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="imageFile">Product Image:</label>
                    <input type="file" id="imageFile" name="imageFile" accept="image/*">
                    <div id="imagePreview" class="image-preview"></div>

                    <label for="specialInstructions">Special Instructions:</label>
                    <input type="text" id="specialInstructions" name="specialInstructions">

                    <button type="submit">Add Product</button>
                </form>
            </div>
        </div>

        <div id="editProductPopup" class="popup">
            <div class="popup-content">
                <span class="close-btn" onclick="closePopup('edit')">&times;</span>
                <h2>Edit Product</h2>
                <form id="editProductForm" enctype="multipart/form-data" method="post">
                    <input type="hidden" id="editProductId" name="editProductId">
                    <input type="hidden" name="editProduct" value="1">
                    <label for="editProductName">Name:</label>
                    <input type="text" id="editProductName" name="editProductName" required>

                    <label for="editCategory">Category:</label>
                    <input type="text" id="editCategory" name="editCategory" list="categoryList" required>
                    <datalist id="categoryList">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>">
                            <?php endforeach; ?>
                    </datalist>

                    <label for="editPrice">Price (BDT):</label>
                    <input type="number" id="editPrice" name="editPrice" min="0" step="0.01" required>

                    <label for="editUnit">Unit:</label>
                    <select id="editUnit" name="editUnit" required>
                        <?php foreach ($units as $unit): ?>
                            <option value="<?php echo htmlspecialchars($unit); ?>"><?php echo htmlspecialchars($unit); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="editImageFile">Product Image:</label>
                    <input type="file" id="editImageFile" name="editImageFile" accept="image/*">
                    <div id="editImagePreview" class="image-preview"></div>

                    <label for="editSpecialInstructions">Special Instructions:</label>
                    <input type="text" id="editSpecialInstructions" name="editSpecialInstructions">

                    <button type="submit">Update Product</button>
                </form>
            </div>
        </div>
    </div>

    <script src="product-list.js?v=<?php echo time(); ?>"></script>
</body>

</html>
<?php
ob_end_flush(); // End output buffering
?>
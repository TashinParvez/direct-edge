<?php
// Prevent any output before JSON response
ob_start(); // Start output buffering
session_start();
include '../include/connect-db.php'; // Database connection

$user_id = $_SESSION['user_id'] ?? 2;

// Helper: get or create active cart for this user
function bow_get_active_cart_id(mysqli $conn, int $user_id): int
{
    $sql = "SELECT cart_id FROM shop_owner_cart_list WHERE user_id = ? AND status = 'active' ORDER BY updated_at DESC LIMIT 1";
    $st = $conn->prepare($sql);
    $st->bind_param('i', $user_id);
    $st->execute();
    $rs = $st->get_result();
    $row = $rs->fetch_assoc();
    $st->close();
    if ($row && isset($row['cart_id'])) {
        return (int)$row['cart_id'];
    }
    $ins = $conn->prepare("INSERT INTO shop_owner_cart_list (user_id, status) VALUES (?, 'active')");
    $ins->bind_param('i', $user_id);
    $ins->execute();
    $new_id = (int)$conn->insert_id;
    $ins->close();
    return $new_id;
}

// Initial cart count for floating cart badge
$cart_id_for_count = bow_get_active_cart_id($conn, (int)$user_id);
$cart_count = 0;
$cntStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM shop_owner_cart_items WHERE cart_id = ?");
if ($cntStmt) {
    $cntStmt->bind_param('i', $cart_id_for_count);
    if ($cntStmt->execute()) {
        $cntRes = $cntStmt->get_result();
        if ($cntRow = $cntRes->fetch_assoc()) {
            $cart_count = (int)$cntRow['cnt'];
        }
    }
    $cntStmt->close();
}

// Handle AJAX request for adding to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    // Clear any previous output
    ob_end_clean();

    if ($_POST['action'] === 'add_to_cart') {
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

        // Validate inputs
        if ($product_id <= 0 || $quantity <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid product ID or quantity']);
            exit;
        }

        // 1. Get product price and available quantity
        $sql = "SELECT p.price, COALESCE(SUM(wp.quantity), 0) AS total_quantity
                FROM products p
                LEFT JOIN warehouse_products wp ON p.product_id = wp.product_id
                WHERE p.product_id = ?
                GROUP BY p.product_id, p.price";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Database query preparation failed: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("i", $product_id);
        if (!$stmt->execute()) {
            echo json_encode(['status' => 'error', 'message' => 'Query execution failed: ' . $stmt->error]);
            $stmt->close();
            exit;
        }
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $price = $row['price'];
            $available_quantity = (int)$row['total_quantity'];
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Product not found']);
            $stmt->close();
            exit;
        }
        $stmt->close();

        // 2. Check if sufficient stock is available
        if ($quantity > $available_quantity) {
            echo json_encode(['status' => 'error', 'message' => 'Requested quantity (' . $quantity . ') exceeds available stock (' . $available_quantity . ')']);
            exit;
        }

        // 3. Ensure we have an active cart_id
        $cart_id = bow_get_active_cart_id($conn, (int)$user_id);

        // 4. Check if product exists in cart (by cart_id)
        $sql = "SELECT cart_item_id, quantity FROM shop_owner_cart_items WHERE cart_id = ? AND product_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Cart query preparation failed: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("ii", $cart_id, $product_id);
        if (!$stmt->execute()) {
            echo json_encode(['status' => 'error', 'message' => 'Cart query execution failed: ' . $stmt->error]);
            $stmt->close();
            exit;
        }
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            // 5a. Update existing item
            $new_qty = $row['quantity'] + $quantity;
            if ($new_qty > $available_quantity) {
                echo json_encode(['status' => 'error', 'message' => 'Total quantity (' . $new_qty . ') exceeds available stock (' . $available_quantity . ')']);
                $stmt->close();
                exit;
            }
            $sql = "UPDATE shop_owner_cart_items SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE cart_item_id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                echo json_encode(['status' => 'error', 'message' => 'Update query preparation failed: ' . $conn->error]);
                exit;
            }
            $stmt->bind_param("ii", $new_qty, $row['cart_item_id']);
            if (!$stmt->execute()) {
                echo json_encode(['status' => 'error', 'message' => 'Update failed: ' . $stmt->error]);
                $stmt->close();
                exit;
            }
        } else {
            // 5b. Insert new item for this cart
            $sql = "INSERT INTO shop_owner_cart_items (cart_id, product_id, quantity, price_at_time) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                echo json_encode(['status' => 'error', 'message' => 'Insert query preparation failed: ' . $conn->error]);
                exit;
            }
            $stmt->bind_param("iiid", $cart_id, $product_id, $quantity, $price);
            if (!$stmt->execute()) {
                echo json_encode(['status' => 'error', 'message' => 'Insert failed: ' . $stmt->error]);
                $stmt->close();
                exit;
            }
        }
        $stmt->close();

        // compute updated cart count
        $cst = $conn->prepare("SELECT COUNT(*) AS cnt FROM shop_owner_cart_items WHERE cart_id = ?");
        $updated_count = 0;
        if ($cst) {
            $cst->bind_param('i', $cart_id);
            if ($cst->execute()) {
                $crs = $cst->get_result();
                if ($crow = $crs->fetch_assoc()) {
                    $updated_count = (int)$crow['cnt'];
                }
            }
            $cst->close();
        }

        echo json_encode(['status' => 'success', 'message' => 'Product added to cart', 'cart_count' => $updated_count]);
        exit;
    }

    if ($_POST['action'] === 'request_stock') {
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

        if ($product_id <= 0 || $quantity <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid product ID or quantity']);
            exit;
        }

        // Insert stock request
        $sql = "INSERT INTO stock_requests (requester_id, product_id, quantity, note, status, requested_at, updated_at) VALUES (?, ?, ?, 'Stock request from shop owner', 'Pending', NOW(), NOW())";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Stock request query preparation failed: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("iii", $user_id, $product_id, $quantity);
        if (!$stmt->execute()) {
            echo json_encode(['status' => 'error', 'message' => 'Stock request insert failed: ' . $stmt->error]);
            $stmt->close();
            exit;
        }
        $stmt->close();

        echo json_encode(['status' => 'success', 'message' => 'Stock request submitted']);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    exit;
}

// Fetch available products
$sql = "SELECT 
            p.product_id,
            p.name AS product_name,
            p.category,
            p.price,
            p.unit,
            COALESCE(SUM(wp.quantity), 0) AS total_quantity,
            COALESCE(SUM(wp.unit_volume), 0) AS total_volume,
            MIN(wp.expiry_date) AS nearest_expiry,
            p.img_url,
            COUNT(DISTINCT w.warehouse_id) AS warehouse_count,
            GROUP_CONCAT(DISTINCT w.name ORDER BY w.name SEPARATOR ', ') AS warehouses,
            GROUP_CONCAT(DISTINCT w.location ORDER BY w.location SEPARATOR ', ') AS warehouse_cities
        FROM products p
        LEFT JOIN warehouse_products wp ON wp.product_id = p.product_id
        LEFT JOIN warehouses w ON wp.warehouse_id = w.warehouse_id
        GROUP BY p.product_id, p.name, p.category, p.price, p.unit, p.img_url
        ORDER BY p.name";
$result = mysqli_query($conn, $sql);
if (!$result) {
    ob_end_clean();
    die("Query failed: " . mysqli_error($conn));
}
$available_products = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_free_result($result);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Buy Products - Stock Integrated</title>
    <link rel="icon" type="image/x-icon" href="../Logo/LogoBG.png">
    <link rel="stylesheet" href="../Include/sidebar.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .product-card {
            transition: all 0.3s ease;
        }
        .product-card:hover {
            background-color: #f8fafc;
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .filter-container {
            transition: all 0.3s ease;
        }
        .filter-container:hover {
            background-color: #f9fafb;
        }
        .search-field {
            transition: all 0.3s ease;
        }
        .search-field:hover {
            background-color: #f3f4f6;
        }
        .search-field:focus {
            background-color: #ffffff;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        .modal {
            animation: fadeIn 0.3s ease-out;
            backdrop-filter: blur(4px);
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .modal-content {
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from { transform: translateY(-30px) scale(0.95); opacity: 0; }
            to { transform: translateY(0) scale(1); opacity: 1; }
        }
        .page-enter {
            animation: pageEnter 0.5s ease-out;
        }
        @keyframes pageEnter {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .floating-btn {
            transition: all 0.3s ease;
        }
        .floating-btn:hover {
            transform: translateY(-2px) scale(1.05);
        }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>

<body class="bg-gray-100 page-enter">
    <?php include '../include/Sidebar.php'; ?>
    
    <section class="home-section p-0">
        <div class="flex justify-between items-center p-6 bg-white shadow-sm border-b">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Buy Products</h1>
                <p class="text-gray-600 mt-1">Browse and purchase products for your store</p>
            </div>
            <div class="flex space-x-3 no-print">
                <a href="cart.php" class="bg-blue-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-600 transition-colors">
                    <i class='bx bx-cart mr-2'></i>View Cart
                </a>
                <a href="requests.php" class="bg-green-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-600 transition-colors">
                    <i class='bx bx-package mr-2'></i>My Requests
                </a>
            </div>
        </div>

        <div class="container mx-auto px-6 py-6">

            <!-- Search and Filters -->
            <div class="bg-white shadow-lg rounded-xl p-6 mb-6 filter-container border border-gray-100">
                <div class="flex items-center mb-4">
                    <i class='bx bx-filter-alt text-xl text-blue-600 mr-2'></i>
                    <h2 class="text-lg font-semibold text-gray-900">Search & Filter Products</h2>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class='bx bx-search mr-1'></i>Search Products
                        </label>
                        <div class="relative">
                            <input type="text" id="searchInput" placeholder="Search products by name..."
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 search-field">
                            <i class='bx bx-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400'></i>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class='bx bx-category mr-1'></i>Category
                        </label>
                        <select id="categoryFilter" class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 search-field">
                            <option value="">All Categories</option>
                            <?php
                            $categories = array_unique(array_column($available_products, 'category'));
                            foreach ($categories as $category) {
                                echo "<option value='".htmlspecialchars($category)."'>".htmlspecialchars($category)."</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class='bx bx-check-circle mr-1'></i>Availability
                        </label>
                        <select id="availabilityFilter" class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 search-field">
                            <option value="">All Products</option>
                            <option value="in_stock">In Stock</option>
                            <option value="out_of_stock">Out of Stock</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="bg-white shadow-md rounded-lg p-4 mb-6 border border-gray-100">
                <div class="flex justify-between items-center">
                    <div class="text-lg font-semibold text-gray-900">
                        Available Products
                    </div>
                    <div class="text-sm text-gray-500" id="productCount">
                        <?= count($available_products) ?> products found
                    </div>
                </div>
            </div>

            <div id="productsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($available_products as $product): ?>
                    <div class="bg-white rounded-xl shadow-md hover:shadow-xl overflow-hidden product-card border border-gray-100">
                        <div class="relative">
                            <img src="../<?= htmlspecialchars($product['img_url']); ?>" alt="<?= htmlspecialchars($product['product_name']); ?>" 
                                class="w-full h-48 object-cover">
                            <?php if ($product['total_quantity'] > 0): ?>
                                <div class="absolute top-3 right-3 bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-semibold">
                                    In Stock
                                </div>
                            <?php else: ?>
                                <div class="absolute top-3 right-3 bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs font-semibold">
                                    Out of Stock
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="p-6">
                            <h3 class="font-bold text-lg mb-2 text-gray-900"><?= htmlspecialchars($product['product_name']); ?></h3>
                            
                            <div class="space-y-2 mb-4">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="flex items-center text-gray-600">
                                        <i class='bx bx-category mr-2'></i>Category:
                                    </span>
                                    <span class="font-medium"><?= htmlspecialchars($product['category']); ?></span>
                                </div>
                                
                                <div class="flex items-center justify-between text-sm">
                                    <span class="flex items-center text-gray-600">
                                        <i class='bx bx-dollar mr-2'></i>Price:
                                    </span>
                                    <span class="font-semibold text-green-600">৳<?= htmlspecialchars($product['price']); ?>/unit</span>
                                </div>
                                
                                <div class="flex items-center justify-between text-sm">
                                    <span class="flex items-center text-gray-600">
                                        <i class='bx bx-package mr-2'></i>Available:
                                    </span>
                                    <span class="font-medium"><?= htmlspecialchars($product['total_quantity']); ?> <?= htmlspecialchars($product['unit']); ?></span>
                                </div>
                                
                                <?php if ($product['warehouses']): ?>
                                    <div class="flex items-start justify-between text-sm">
                                        <span class="flex items-center text-gray-600">
                                            <i class='bx bx-buildings mr-2'></i>Warehouses:
                                        </span>
                                        <span class="font-medium text-right text-xs"><?= htmlspecialchars($product['warehouses']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($product['total_quantity'] > 0): ?>
                                <button class="w-full bg-green-500 text-white py-3 px-4 rounded-lg hover:bg-green-600 transition-colors font-medium"
                                    onclick='openAddCartModal(<?= json_encode([
                                        'product_id' => $product['product_id'],
                                        'name' => $product['product_name'],
                                        'category' => $product['category'],
                                        'price' => $product['price'],
                                        'unit' => $product['unit'],
                                        'total_quantity' => $product['total_quantity'],
                                        'img_url' => $product['img_url']
                                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                    <i class='bx bx-cart-add mr-2'></i>Add to Cart
                                </button>
                            <?php else: ?>
                                <button class="w-full bg-yellow-500 text-white py-3 px-4 rounded-lg hover:bg-yellow-600 transition-colors font-medium"
                                    onclick='openRequestStockModal(<?= json_encode([
                                        'product_id' => $product['product_id'],
                                        'name' => $product['product_name'],
                                        'price' => $product['price'],
                                        'img_url' => $product['img_url']
                                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                    <i class='bx bx-package mr-2'></i>Request Stock
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Empty State -->
            <div id="emptyState" class="hidden bg-white rounded-xl p-16 text-center">
                <i class='bx bx-search text-6xl text-gray-300 mb-4'></i>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">No products found</h3>
                <p class="text-gray-500">Try adjusting your search criteria or filters</p>
            </div>

        </div>
    </section>

    <!-- Floating Action Buttons -->
    <div class="fixed bottom-6 right-6 flex flex-col space-y-3 no-print">
        <a href="cart.php" class="floating-btn bg-blue-500 text-white p-4 rounded-full shadow-lg hover:bg-blue-600 flex items-center justify-center">
            <i class='bx bx-cart text-xl'></i>
        </a>
        <a href="requests.php" class="floating-btn bg-green-500 text-white p-4 rounded-full shadow-lg hover:bg-green-600 flex items-center justify-center">
            <i class='bx bx-package text-xl'></i>
        </a>
    </div>

    <!-- Add to Cart Modal -->
    <div id="addCartModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 modal">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl modal-content border border-gray-200 m-4">
            <div class="flex justify-between items-center p-6 border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-900">Add to Cart</h2>
                <button onclick="closeModal('addCartModal')" class="text-gray-400 hover:text-gray-600 text-3xl">
                    <i class='bx bx-x'></i>
                </button>
            </div>
            
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <img id="cartProductImg" src="" alt="Product Image" class="w-full h-64 object-cover rounded-lg">
                    </div>
                    
                    <div class="space-y-4">
                        <h3 id="cartProductName" class="text-2xl font-bold text-gray-900"></h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Category:</span>
                                <span id="cartProductCategory" class="font-medium"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Available:</span>
                                <span id="cartProductAvailable" class="font-medium text-green-600"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Unit Price:</span>
                                <span id="cartProductPrice" class="font-semibold text-blue-600"></span>
                            </div>
                        </div>
                        
                        <div class="border-t border-gray-200 pt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Quantity:</label>
                            <input type="number" id="cartProductQty" min="1" value="1" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex justify-between text-lg font-bold">
                                <span>Total Price:</span>
                                <span id="cartTotalPrice" class="text-green-600"></span>
                            </div>
                        </div>
                        
                        <button id="confirmAddCart" class="w-full bg-green-500 text-white py-3 px-6 rounded-lg hover:bg-green-600 transition-colors font-semibold">
                            <i class='bx bx-cart-add mr-2'></i>Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Request Stock Modal -->
    <div id="requestStockModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 modal">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl modal-content border border-gray-200 m-4">
            <div class="flex justify-between items-center p-6 border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-900">Request Stock</h2>
                <button onclick="closeModal('requestStockModal')" class="text-gray-400 hover:text-gray-600 text-3xl">
                    <i class='bx bx-x'></i>
                </button>
            </div>
            
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <img id="requestProductImg" src="" alt="Product Image" class="w-full h-64 object-cover rounded-lg">
                    </div>
                    
                    <div class="space-y-4">
                        <h3 id="requestProductName" class="text-2xl font-bold text-gray-900"></h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Current Price:</span>
                                <span id="requestProductPrice" class="font-semibold text-blue-600"></span>
                            </div>
                        </div>
                        
                        <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg">
                            <div class="flex items-center">
                                <i class='bx bx-info-circle text-yellow-600 mr-2'></i>
                                <p class="text-sm text-yellow-800">Price may change when stock becomes available</p>
                            </div>
                        </div>
                        
                        <div class="border-t border-gray-200 pt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Quantity Needed:</label>
                            <input type="number" id="requestProductQty" min="1" value="1" 
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        
                        <button id="confirmRequestStock" class="w-full bg-yellow-500 text-white py-3 px-6 rounded-lg hover:bg-yellow-600 transition-colors font-semibold">
                            <i class='bx bx-package mr-2'></i>Submit Request
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        const USER_ID = <?= json_encode($user_id); ?>;
        let currentProduct = null;

        // Filter functionality
        function filterProducts() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const category = document.getElementById('categoryFilter').value;
            const availability = document.getElementById('availabilityFilter').value;

            const products = <?= json_encode($available_products); ?>;
            const grid = document.getElementById('productsGrid');
            const emptyState = document.getElementById('emptyState');
            const productCount = document.getElementById('productCount');
            
            grid.innerHTML = '';
            let visibleCount = 0;

            products.forEach(product => {
                const matchesSearch = product.product_name.toLowerCase().includes(search);
                const matchesCategory = !category || product.category === category;
                const matchesAvailability = !availability ||
                    (availability === 'in_stock' && product.total_quantity > 0) ||
                    (availability === 'out_of_stock' && product.total_quantity === 0);

                if (matchesSearch && matchesCategory && matchesAvailability) {
                    visibleCount++;
                    const div = document.createElement('div');
                    div.className = 'bg-white rounded-xl shadow-md hover:shadow-xl overflow-hidden product-card border border-gray-100';
                    
                    const stockBadge = product.total_quantity > 0 
                        ? '<div class="absolute top-3 right-3 bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-semibold">In Stock</div>'
                        : '<div class="absolute top-3 right-3 bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs font-semibold">Out of Stock</div>';
                    
                    const actionButton = product.total_quantity > 0
                        ? `<button class="w-full bg-green-500 text-white py-3 px-4 rounded-lg hover:bg-green-600 transition-colors font-medium" onclick='openAddCartModal(${JSON.stringify(product)})'>
                             <i class='bx bx-cart-add mr-2'></i>Add to Cart
                           </button>`
                        : `<button class="w-full bg-yellow-500 text-white py-3 px-4 rounded-lg hover:bg-yellow-600 transition-colors font-medium" onclick='openRequestStockModal(${JSON.stringify({
                             product_id: product.product_id,
                             name: product.product_name,
                             price: product.price,
                             img_url: product.img_url
                           })})'>
                             <i class='bx bx-package mr-2'></i>Request Stock
                           </button>`;
                    
                    div.innerHTML = `
                        <div class="relative">
                            <img src="../${product.img_url}" alt="${product.product_name}" class="w-full h-48 object-cover">
                            ${stockBadge}
                        </div>
                        
                        <div class="p-6">
                            <h3 class="font-bold text-lg mb-2 text-gray-900">${product.product_name}</h3>
                            
                            <div class="space-y-2 mb-4">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="flex items-center text-gray-600">
                                        <i class='bx bx-category mr-2'></i>Category:
                                    </span>
                                    <span class="font-medium">${product.category}</span>
                                </div>
                                
                                <div class="flex items-center justify-between text-sm">
                                    <span class="flex items-center text-gray-600">
                                        <i class='bx bx-dollar mr-2'></i>Price:
                                    </span>
                                    <span class="font-semibold text-green-600">৳${product.price}/unit</span>
                                </div>
                                
                                <div class="flex items-center justify-between text-sm">
                                    <span class="flex items-center text-gray-600">
                                        <i class='bx bx-package mr-2'></i>Available:
                                    </span>
                                    <span class="font-medium">${product.total_quantity} ${product.unit}</span>
                                </div>
                                
                                ${product.warehouses ? `
                                <div class="flex items-start justify-between text-sm">
                                    <span class="flex items-center text-gray-600">
                                        <i class='bx bx-buildings mr-2'></i>Warehouses:
                                    </span>
                                    <span class="font-medium text-right text-xs">${product.warehouses}</span>
                                </div>` : ''}
                            </div>
                            
                            ${actionButton}
                        </div>
                    `;
                    grid.appendChild(div);
                }
            });

            if (visibleCount === 0) {
                emptyState.classList.remove('hidden');
                grid.classList.add('hidden');
            } else {
                emptyState.classList.add('hidden');
                grid.classList.remove('hidden');
            }

            productCount.textContent = `${visibleCount} products found`;
        }

        // Event listeners for filters
        document.getElementById('searchInput').addEventListener('input', filterProducts);
        document.getElementById('categoryFilter').addEventListener('change', filterProducts);
        document.getElementById('availabilityFilter').addEventListener('change', filterProducts);

        // Modal functions
        function openAddCartModal(product) {
            currentProduct = product;
            document.getElementById('cartProductImg').src = '../' + product.img_url;
            document.getElementById('cartProductName').innerText = product.name;
            document.getElementById('cartProductCategory').innerText = product.category;
            document.getElementById('cartProductAvailable').innerText = product.total_quantity + ' ' + product.unit;
            document.getElementById('cartProductPrice').innerText = '৳' + product.price + '/unit';
            document.getElementById('cartProductQty').value = 1;
            document.getElementById('cartProductQty').max = product.total_quantity;
            document.getElementById('cartTotalPrice').innerText = '৳' + product.price;

            document.getElementById('addCartModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';

            document.getElementById('cartProductQty').oninput = function() {
                let qty = Math.max(1, Math.min(this.value, product.total_quantity));
                this.value = qty;
                document.getElementById('cartTotalPrice').innerText = '৳' + (qty * product.price).toFixed(2);
            };
        }

        function openRequestStockModal(product) {
            currentProduct = product;
            document.getElementById('requestProductImg').src = '../' + product.img_url;
            document.getElementById('requestProductName').innerText = product.name;
            document.getElementById('requestProductPrice').innerText = '৳' + product.price + '/unit';
            document.getElementById('requestProductQty').value = 1;

            document.getElementById('requestStockModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
            document.body.style.overflow = 'auto';
            currentProduct = null;
        }

                const quantity = parseInt(document.getElementById('cartProductQty').value);

                fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `action=add_to_cart&user_id=${USER_ID}&product_id=${currentProduct.product_id}&quantity=${quantity}`
                    })
                    .then(res => {
                        if (!res.ok) {
                            return res.text().then(text => {
                                throw new Error(`HTTP error! Status: ${res.status}, Response: ${text}`);
                            });
                        }
                        return res.json();
                    })
                    .then(data => {
                        if (data.status === 'success') {
                            // Success: close modal and update cart badge without alert
                            closeModal('addCartModal');
                            if (typeof data.cart_count !== 'undefined') {
                                const cc = document.getElementById('cart-count');
                                if (cc) cc.textContent = String(data.cart_count);
                            }
                        } else {
                            // Error: show message
                            alert(data.message || 'Failed to add to cart');
                        }
                    })
                    .catch(err => {
                        console.error('Error adding to cart:', err);
                        alert('Error adding to cart: ' + err.message);
                    });
            };

            const quantity = parseInt(document.getElementById('cartProductQty').value);
            const button = this;
            const originalText = button.innerHTML;
            
            button.innerHTML = '<i class="bx bx-loader-alt bx-spin mr-2"></i>Adding...';
            button.disabled = true;

            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=add_to_cart&user_id=${USER_ID}&product_id=${currentProduct.product_id}&quantity=${quantity}`
            })
            .then(res => res.json())
            .then(data => {
                // Show success/error message
                const messageDiv = document.createElement('div');
                messageDiv.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${
                    data.status === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
                }`;
                messageDiv.innerHTML = `<i class='bx ${data.status === 'success' ? 'bx-check' : 'bx-x'} mr-2'></i>${data.message}`;
                document.body.appendChild(messageDiv);

                setTimeout(() => {
                    messageDiv.remove();
                }, 3000);

                if (data.status === 'success') {
                    closeModal('addCartModal');
                }
            })
            .catch(err => {
                console.error('Error adding to cart:', err);
                alert('Error adding to cart: ' + err.message);
            })
            .finally(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            });
        };

        document.getElementById('confirmRequestStock').onclick = function() {
            if (!currentProduct) {
                alert('No product selected');
                return;
            }

            const quantity = parseInt(document.getElementById('requestProductQty').value);
            const button = this;
            const originalText = button.innerHTML;
            
            button.innerHTML = '<i class="bx bx-loader-alt bx-spin mr-2"></i>Requesting...';
            button.disabled = true;

            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=request_stock&user_id=${USER_ID}&product_id=${currentProduct.product_id}&quantity=${quantity}`
            })
            .then(res => res.json())
            .then(data => {
                // Show success/error message
                const messageDiv = document.createElement('div');
                messageDiv.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${
                    data.status === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
                }`;
                messageDiv.innerHTML = `<i class='bx ${data.status === 'success' ? 'bx-check' : 'bx-x'} mr-2'></i>${data.message}`;
                document.body.appendChild(messageDiv);

                setTimeout(() => {
                    messageDiv.remove();
                }, 3000);

                if (data.status === 'success') {
                    closeModal('requestStockModal');
                }
            })
            .catch(err => {
                console.error('Error requesting stock:', err);
                alert('Error requesting stock: ' + err.message);
            })
            .finally(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            });
        };

        // Close modals on escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal('addCartModal');
                closeModal('requestStockModal');
            }
        });

        // Close modals on backdrop click
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                closeModal('addCartModal');
                closeModal('requestStockModal');
            }
        };
    </script>
</body>

</html>
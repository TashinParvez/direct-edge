<?php
ob_start(); // Start output buffering to handle session_start() in sidebar
?>

<link rel="stylesheet" href="../Include/sidebar.css">
<?php include '../Include/SidebarAgent.php'; ?>
<?php
// Ensure session is started and output buffering is enabled before including any files
// that may emit HTML (like the navbar). This lets POST handlers clean the buffer
// and return pure JSON responses to AJAX callers.
// if (session_status() === PHP_SESSION_NONE) session_start();

// ob_start(); // Start output buffering so we can clear HTML when returning JSON
// include '../include/navbar.php';
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
            ob_end_clean();
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
            ob_end_clean();
            echo json_encode(['status' => 'error', 'message' => 'Database query preparation failed: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("i", $product_id);
        if (!$stmt->execute()) {
            ob_end_clean();
            echo json_encode(['status' => 'error', 'message' => 'Query execution failed: ' . $stmt->error]);
            $stmt->close();
            exit;
        }
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $price = $row['price'];
            $available_quantity = (int)$row['total_quantity'];
        } else {
            ob_end_clean();
            echo json_encode(['status' => 'error', 'message' => 'Product not found']);
            $stmt->close();
            exit;
        }
        $stmt->close();

        // 2. Check if sufficient stock is available
        if ($quantity > $available_quantity) {
            ob_end_clean();
            echo json_encode(['status' => 'error', 'message' => 'Requested quantity (' . $quantity . ') exceeds available stock (' . $available_quantity . ')']);
            exit;
        }

        // 3. Ensure we have an active cart_id
        $cart_id = bow_get_active_cart_id($conn, (int)$user_id);

        // 4. Check if product exists in cart (by cart_id)
        $sql = "SELECT cart_item_id, quantity FROM shop_owner_cart_items WHERE cart_id = ? AND product_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            ob_end_clean();
            echo json_encode(['status' => 'error', 'message' => 'Cart query preparation failed: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("ii", $cart_id, $product_id);
        if (!$stmt->execute()) {
            ob_end_clean();
            echo json_encode(['status' => 'error', 'message' => 'Cart query execution failed: ' . $stmt->error]);
            $stmt->close();
            exit;
        }
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            // 5a. Update existing item
            $new_qty = $row['quantity'] + $quantity;
            if ($new_qty > $available_quantity) {
                ob_end_clean();
                echo json_encode(['status' => 'error', 'message' => 'Total quantity (' . $new_qty . ') exceeds available stock (' . $available_quantity . ')']);
                $stmt->close();
                exit;
            }
            $sql = "UPDATE shop_owner_cart_items SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE cart_item_id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                ob_end_clean();
                echo json_encode(['status' => 'error', 'message' => 'Update query preparation failed: ' . $conn->error]);
                exit;
            }
            $stmt->bind_param("ii", $new_qty, $row['cart_item_id']);
            if (!$stmt->execute()) {
                ob_end_clean();
                echo json_encode(['status' => 'error', 'message' => 'Update failed: ' . $stmt->error]);
                $stmt->close();
                exit;
            }
        } else {
            // 5b. Insert new item for this cart
            $sql = "INSERT INTO shop_owner_cart_items (cart_id, product_id, quantity, price_at_time) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                ob_end_clean();
                echo json_encode(['status' => 'error', 'message' => 'Insert query preparation failed: ' . $conn->error]);
                exit;
            }
            $stmt->bind_param("iiid", $cart_id, $product_id, $quantity, $price);
            if (!$stmt->execute()) {
                ob_end_clean();
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

        ob_end_clean();
        echo json_encode(['status' => 'success', 'message' => 'Product added to cart', 'cart_count' => $updated_count]);
        exit;
    }

    if ($_POST['action'] === 'request_stock') {
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

        if ($product_id <= 0 || $quantity <= 0) {
            ob_end_clean();
            echo json_encode(['status' => 'error', 'message' => 'Invalid product ID or quantity']);
            exit;
        }

        // Insert stock request
        $sql = "INSERT INTO stock_requests (requester_id, status, notes) VALUES (?, 'Pending', 'Stock request from shop owner')";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            ob_end_clean();
            echo json_encode(['status' => 'error', 'message' => 'Stock request query preparation failed: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("i", $user_id);
        if (!$stmt->execute()) {
            ob_end_clean();
            echo json_encode(['status' => 'error', 'message' => 'Stock request insert failed: ' . $stmt->error]);
            $stmt->close();
            exit;
        }
        $request_id = $stmt->insert_id;
        $stmt->close();

        // Insert stock request item
        $sql = "INSERT INTO stock_request_items (request_id, product_id, quantity) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            ob_end_clean();
            echo json_encode(['status' => 'error', 'message' => 'Stock request item query preparation failed: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("iii", $request_id, $product_id, $quantity);
        if (!$stmt->execute()) {
            ob_end_clean();
            echo json_encode(['status' => 'error', 'message' => 'Stock request item insert failed: ' . $stmt->error]);
            $stmt->close();
            exit;
        }
        $stmt->close();

        ob_end_clean();
        echo json_encode(['status' => 'success', 'message' => 'Stock request submitted']);
        exit;
    }

    ob_end_clean();
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

<!-- Html Start -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy Products</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        .product-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }

        .modal {
            animation: fadeIn 0.2s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.98);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .input-field,
        .select-field {
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .input-field:focus,
        .select-field:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn {
            transition: background-color 0.2s ease, transform 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .loading::after {
            content: '';
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid #fff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.8s linear infinite;
            margin-left: 8px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 1280px) {
            .grid-cols-5 {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        @media (max-width: 1024px) {
            .grid-cols-5 {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            .grid-cols-5 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .grid-cols-5 {
                grid-template-columns: 1fr;
            }

            .modal-content {
                flex-direction: column;
            }
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <!-- Sidebar -->
    <?php include '../Include/SidebarShop.php'; ?>


    <!-- Main Content -->
    <div class="flex-1 p-4 sm:p-6 lg:p-8">
        <h2 class="text-2xl font-semibold text-gray-900 mb-8 text-center">Buy Products For Your Shop</h2>

        <!-- Search and Filters -->
        <div class="mb-8 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex flex-col sm:flex-row gap-4">
                <input type="text" id="searchInput" placeholder="Search products..."
                    class="flex-1 px-4 py-2 border border-gray-200 rounded-lg input-field focus:outline-none focus:ring-0"
                    oninput="filterProducts()">
                <div class="flex gap-4 flex-wrap">
                    <select id="categoryFilter"
                        class="px-4 py-2 border border-gray-200 rounded-lg select-field focus:outline-none"
                        onchange="filterProducts()">
                        <option value="">All Categories</option>
                        <?php
                        $categories = array_unique(array_column($available_products, 'category'));
                        foreach ($categories as $category) {
                            echo "<option value='$category'>$category</option>";
                        }
                        ?>
                    </select>
                    <select id="warehouseFilter"
                        class="px-4 py-2 border border-gray-200 rounded-lg select-field focus:outline-none"
                        onchange="filterProducts()">
                        <option value="">All Warehouses</option>
                        <?php
                        $warehouses = [];
                        foreach ($available_products as $product) {
                            if ($product['warehouses']) {
                                $warehouse_list = explode(', ', $product['warehouses']);
                                $warehouses = array_merge($warehouses, $warehouse_list);
                            }
                        }
                        $warehouses = array_unique($warehouses);
                        foreach ($warehouses as $warehouse) {
                            echo "<option value='$warehouse'>$warehouse</option>";
                        }
                        ?>
                    </select>
                    <select id="availabilityFilter"
                        class="px-4 py-2 border border-gray-200 rounded-lg select-field focus:outline-none"
                        onchange="filterProducts()">
                        <option value="">All Availability</option>
                        <option value="in_stock">In Stock</option>
                        <option value="out_of_stock">Out of Stock</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div id="productsGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
            <?php foreach ($available_products as $product): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col product-card">
                    <img src="../<?php echo htmlspecialchars($product['img_url']); ?>"
                        alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                        class="w-full h-48 object-cover rounded-t-xl">
                    <div class="p-5 flex-1 flex flex-col">
                        <h3 class="font-semibold text-lg text-gray-900 mb-2">
                            <?php echo htmlspecialchars($product['product_name']); ?></h3>
                        <p class="text-sm text-gray-500 mb-1">Category:
                            <?php echo htmlspecialchars($product['category']); ?></p>
                        <p class="text-sm text-gray-900 font-medium mb-1">
                            ৳<?php echo htmlspecialchars($product['price']); ?>/unit</p>
                        <p class="text-sm text-gray-500 mb-4">Available:
                            <?php echo htmlspecialchars($product['total_quantity']); ?>
                            <?php echo htmlspecialchars($product['unit']); ?></p>
                        <?php if ($product['total_quantity'] > 0): ?>
                            <button class="mt-auto bg-blue-600 text-white px-4 py-2 rounded-lg btn" onclick='openAddCartModal(<?php echo json_encode([
                                                                                                                                    'product_id' => $product['product_id'],
                                                                                                                                    'name' => $product['product_name'],
                                                                                                                                    'category' => $product['category'],
                                                                                                                                    'price' => $product['price'],
                                                                                                                                    'unit' => $product['unit'],
                                                                                                                                    'total_quantity' => $product['total_quantity'],
                                                                                                                                    'img_url' => $product['img_url']
                                                                                                                                ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                Add to Cart
                            </button>
                        <?php else: ?>
                            <button class="mt-auto bg-gray-600 text-white px-4 py-2 rounded-lg btn" onclick='openRequestStockModal(<?php echo json_encode([
                                                                                                                                        'product_id' => $product['product_id'],
                                                                                                                                        'name' => $product['product_name'],
                                                                                                                                        'price' => $product['price'],
                                                                                                                                        'img_url' => $product['img_url']
                                                                                                                                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                Request Stock
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Floating Buttons -->
        <div class="fixed bottom-6 right-6 flex flex-col gap-3 z-40">
            <button onclick="window.location.href='Checkout/checkout.php'"
                class="relative bg-blue-600 text-white px-5 py-3 rounded-full shadow-lg btn flex items-center">
                <i class='bx bx-cart text-lg mr-2'></i>Cart
                <span id="cart-count"
                    class="absolute -top-2 -right-2 inline-flex items-center justify-center text-xs font-semibold bg-red-500 text-white rounded-full w-5 h-5">
                    <?php echo (int)$cart_count; ?>
                </span>
            </button>
            <button onclick="window.location.href='requests.php'"
                class="bg-gray-600 text-white px-5 py-3 rounded-full shadow-lg btn flex items-center">
                <i class='bx bx-package text-lg mr-2'></i>Requests
            </button>
        </div>

        <!-- Add to Cart Modal -->
        <div id="addCartModal"
            class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden z-50">
            <div class="bg-white rounded-xl max-w-2xl w-full p-6 modal-content modal">
                <button onclick="closeModal('addCartModal')"
                    class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl">&times;</button>
                <div class="flex flex-col sm:flex-row gap-6">
                    <div class="sm:w-1/2">
                        <img id="cartProductImg" src="" alt="Product Image" class="w-full h-64 object-cover rounded-lg">
                    </div>
                    <div class="sm:w-1/2 flex flex-col">
                        <h2 id="cartProductName" class="text-xl font-semibold text-gray-900 mb-3"></h2>
                        <p id="cartProductCategory" class="text-sm text-gray-500 mb-2"></p>
                        <p class="text-sm text-gray-500 mb-2">Available: <span id="cartProductAvailable"></span></p>
                        <p class="text-sm text-gray-900 font-medium mb-3">৳<span id="cartProductPrice"></span>/unit</p>
                        <label class="text-sm text-gray-600 mb-1">Quantity</label>
                        <input type="number" id="cartProductQty" min="1" value="1"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg input-field focus:outline-none mb-3">
                        <p class="text-sm text-gray-900 font-medium">Total: ৳<span id="cartTotalPrice"></span></p>
                        <button id="confirmAddCart" class="mt-4 bg-blue-600 text-white px-4 py-2 rounded-lg btn">Add to
                            Cart</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Request Stock Modal -->
        <div id="requestStockModal"
            class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden z-50">
            <div class="bg-white rounded-xl max-w-2xl w-full p-6 modal-content modal">
                <button onclick="closeModal('requestStockModal')"
                    class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl">&times;</button>
                <div class="flex flex-col sm:flex-row gap-6">
                    <div class="sm:w-1/2">
                        <img id="requestProductImg" src="" alt="Product Image"
                            class="w-full h-64 object-cover rounded-lg">
                    </div>
                    <div class="sm:w-1/2 flex flex-col">
                        <h2 id="requestProductName" class="text-xl font-semibold text-gray-900 mb-3"></h2>
                        <p class="text-sm text-gray-900 font-medium mb-2">Today's Price: ৳<span
                                id="requestProductPrice"></span>/unit</p>
                        <p class="text-xs text-gray-400 mb-3">*Price may change when stock is updated</p>
                        <label class="text-sm text-gray-600 mb-1">Quantity Needed</label>
                        <input type="number" id="requestProductQty" min="1" value="1"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg input-field focus:outline-none mb-3">
                        <button id="confirmRequestStock"
                            class="mt-4 bg-gray-600 text-white px-4 py-2 rounded-lg btn">Request Stock</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- JavaScript -->
        <script>
            const USER_ID = <?php echo json_encode($user_id); ?>;
            let currentProduct = null;

            function openAddCartModal(product) {
                currentProduct = product;
                document.getElementById('cartProductImg').src = '../' + product.img_url;
                document.getElementById('cartProductName').textContent = product.name;
                document.getElementById('cartProductCategory').textContent = "Category: " + product.category;
                document.getElementById('cartProductAvailable').textContent = product.total_quantity + " " + product.unit;
                document.getElementById('cartProductPrice').textContent = product.price;
                document.getElementById('cartProductQty').value = 1;
                document.getElementById('cartTotalPrice').textContent = product.price;

                document.getElementById('addCartModal').classList.remove('hidden');

                document.getElementById('cartProductQty').oninput = function() {
                    let qty = Math.max(1, Math.min(this.value, product.total_quantity));
                    this.value = qty;
                    document.getElementById('cartTotalPrice').textContent = (qty * product.price).toFixed(2);
                };
            }

            function openRequestStockModal(product) {
                currentProduct = product;
                document.getElementById('requestProductImg').src = '../' + product.img_url;
                document.getElementById('requestProductName').textContent = product.name;
                document.getElementById('requestProductPrice').textContent = product.price;
                document.getElementById('requestProductQty').value = 1;

                document.getElementById('requestStockModal').classList.remove('hidden');
            }

            function closeModal(modalId) {
                document.getElementById(modalId).classList.add('hidden');
                currentProduct = null;
            }

            window.onclick = function(event) {
                const addCartModal = document.getElementById('addCartModal');
                const requestStockModal = document.getElementById('requestStockModal');
                if (event.target === addCartModal) closeModal('addCartModal');
                if (event.target === requestStockModal) closeModal('requestStockModal');
            };

            document.getElementById('confirmAddCart').onclick = function() {
                if (!currentProduct) {
                    alert('No product selected');
                    return;
                }

                const button = this;
                button.disabled = true;
                button.classList.add('loading');
                button.textContent = 'Adding...';

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
                        button.disabled = false;
                        button.classList.remove('loading');
                        button.textContent = 'Add to Cart';

                        if (data.status === 'success') {
                            closeModal('addCartModal');
                            if (typeof data.cart_count !== 'undefined') {
                                const cc = document.getElementById('cart-count');
                                if (cc) cc.textContent = String(data.cart_count);
                            }
                        } else {
                            alert(data.message || 'Failed to add to cart');
                        }
                    })
                    .catch(err => {
                        button.disabled = false;
                        button.classList.remove('loading');
                        button.textContent = 'Add to Cart';
                        console.error('Error adding to cart:', err);
                        alert('Error adding to cart: ' + err.message);
                    });
            };

            document.getElementById('confirmRequestStock').onclick = function() {
                if (!currentProduct) {
                    alert('No product selected');
                    return;
                }

                const button = this;
                button.disabled = true;
                button.classList.add('loading');
                button.textContent = 'Requesting...';

                const quantity = parseInt(document.getElementById('requestProductQty').value);

                fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `action=request_stock&user_id=${USER_ID}&product_id=${currentProduct.product_id}&quantity=${quantity}`
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
                        button.disabled = false;
                        button.classList.remove('loading');
                        button.textContent = 'Request Stock';

                        if (data.status === 'success') {
                            closeModal('requestStockModal');
                        } else {
                            alert(data.message || 'Failed to submit stock request');
                        }
                    })
                    .catch(err => {
                        button.disabled = false;
                        button.classList.remove('loading');
                        button.textContent = 'Request Stock';
                        console.error('Error requesting stock:', err);
                        alert('Error requesting stock: ' + err.message);
                    });
            };

            function filterProducts() {
                const search = document.getElementById('searchInput').value.toLowerCase();
                const category = document.getElementById('categoryFilter').value;
                const warehouse = document.getElementById('warehouseFilter').value;
                const availability = document.getElementById('availabilityFilter').value;

                const products = <?php echo json_encode($available_products); ?>;
                const grid = document.getElementById('productsGrid');
                grid.innerHTML = '';

                products.forEach(product => {
                    const matchesSearch = product.product_name.toLowerCase().includes(search);
                    const matchesCategory = !category || product.category === category;
                    const matchesWarehouse = !warehouse || (product.warehouses && product.warehouses.includes(
                        warehouse));
                    const matchesAvailability = !availability ||
                        (availability === 'in_stock' && product.total_quantity > 0) ||
                        (availability === 'out_of_stock' && product.total_quantity === 0);

                    if (matchesSearch && matchesCategory && matchesWarehouse && matchesAvailability) {
                        const div = document.createElement('div');
                        div.className =
                            'bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col product-card';
                        div.innerHTML = `
                            <img src="../${product.img_url}" alt="${product.product_name}" class="w-full h-48 object-cover rounded-t-xl">
                            <div class="p-5 flex-1 flex flex-col">
                                <h3 class="font-semibold text-lg text-gray-900 mb-2">${product.product_name}</h3>
                                <p class="text-sm text-gray-500 mb-1">Category: ${product.category}</p>
                                <p class="text-sm text-gray-900 font-medium mb-1">৳${product.price}/unit</p>
                                <p class="text-sm text-gray-500 mb-4">Available: ${product.total_quantity} ${product.unit}</p>
                                ${product.total_quantity > 0 ?
                                    `<button class="mt-auto bg-blue-600 text-white px-4 py-2 rounded-lg btn" onclick='openAddCartModal(${JSON.stringify(product)})'>Add to Cart</button>` :
                                    `<button class="mt-auto bg-gray-600 text-white px-4 py-2 rounded-lg btn" onclick='openRequestStockModal(${JSON.stringify({
                                        product_id: product.product_id,
                                        name: product.product_name,
                                        price: product.price,
                                        img_url: product.img_url
                                    })})'>Request Stock</button>`}
                            </div>`;
                        grid.appendChild(div);
                    }
                });
            }
        </script>
    </div>
</body>

</html>
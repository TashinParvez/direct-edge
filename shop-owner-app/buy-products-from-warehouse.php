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

        echo json_encode(['status' => 'success', 'message' => 'Product added to cart']);
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
        $sql = "INSERT INTO stock_requests (requester_id, status, notes) VALUES (?, 'Pending', 'Stock request from shop owner')";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Stock request query preparation failed: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("i", $user_id);
        if (!$stmt->execute()) {
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
            echo json_encode(['status' => 'error', 'message' => 'Stock request item query preparation failed: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("iii", $request_id, $product_id, $quantity);
        if (!$stmt->execute()) {
            echo json_encode(['status' => 'error', 'message' => 'Stock request item insert failed: ' . $stmt->error]);
            $stmt->close();
            exit;
        }
        $stmt->close();

        echo json_encode(['status' => 'success', 'message' => 'Stock request submitted']);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    exit;

    // =============================== request Stock =============================
    $sql = "INSERT INTO `stock_requests` (`request_id`, `requester_id`, `product_id`, `quantity`, `note`, `status`, `requested_at`, `updated_at`) 
    VALUES (NULL, '6', '1', '0600', 'Urgent', 'Pending', current_timestamp(), current_timestamp());";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy Products</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 flex">
    <!-- Sidebar -->
    <?php
    //  include '../include/Sidebar.php'; 
    ?>

    <!-- Main Content -->
    <div class="flex-1 p-6 ml-64">
        <h2 class="text-2xl font-bold mb-6 text-center">Buy Products For Your Store</h2>

        <!-- Search Bar -->
        <div class="mb-4">
            <input type="text" id="searchInput" placeholder="Search products..." class="w-full p-3 border border-gray-300 rounded shadow-sm" oninput="filterProducts()">
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-4 mb-6">
            <select id="categoryFilter" class="p-2 border border-gray-300 rounded shadow-sm" onchange="filterProducts()">
                <option value="">All Categories</option>
                <?php
                $categories = array_unique(array_column($available_products, 'category'));
                foreach ($categories as $category) {
                    echo "<option value='$category'>$category</option>";
                }
                ?>
            </select>
            <select id="warehouseFilter" class="p-2 border border-gray-300 rounded shadow-sm" onchange="filterProducts()">
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
            <select id="availabilityFilter" class="p-2 border border-gray-300 rounded shadow-sm" onchange="filterProducts()">
                <option value="">Availability</option>
                <option value="in_stock">In Stock</option>
                <option value="out_of_stock">Out of Stock</option>
            </select>
        </div>

        <!-- Products Grid -->
        <div id="productsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($available_products as $product): ?>
                <div class="bg-white rounded shadow-md overflow-hidden flex flex-col justify-between">
                    <img src="../<?php echo htmlspecialchars($product['img_url']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" class="w-full h-48 object-cover">
                    <div class="p-4 flex-1 flex flex-col justify-between">
                        <div>
                            <h3 class="font-bold text-lg mb-1"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                            <p class="text-gray-600 mb-1">Category: <?php echo htmlspecialchars($product['category']); ?></p>
                            <p class="text-gray-800 font-semibold mb-1">Price: <?php echo htmlspecialchars($product['price']); ?> BDT/unit</p>
                            <p class="text-gray-600 mb-1">Available: <?php echo htmlspecialchars($product['total_quantity']); ?> <?php echo htmlspecialchars($product['unit']); ?></p>
                        </div>
                        <?php if ($product['total_quantity'] > 0): ?>
                            <button class="mt-3 bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600 w-full"
                                onclick='openAddCartModal(<?php echo json_encode([
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
                            <button class="mt-3 bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600 w-full"
                                onclick='openRequestStockModal(<?php echo json_encode([
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
        <div class="fixed bottom-6 right-6 flex flex-col gap-3">
            <!-- <button onclick="window.location.href='cart.php'" class="bg-blue-600 text-white px-4 py-3 rounded-full shadow-lg hover:bg-blue-700">🛒 Cart</button> -->
            <button onclick="window.location.href='Checkout/checkout.php'" class="bg-blue-600 text-white px-4 py-3 rounded-full shadow-lg hover:bg-blue-700">🛒 Cart</button>
            <button onclick="window.location.href='requests.php'" class="bg-red-600 text-white px-4 py-3 rounded-full shadow-lg hover:bg-red-700">📦 Requests</button>
        </div>

        <!-- Add to Cart Modal -->
        <div id="addCartModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
            <div class="bg-white rounded-xl shadow-xl max-w-3xl w-full relative p-6 flex gap-6">
                <button onclick="closeModal('addCartModal')" class="absolute top-3 right-3 text-gray-500 hover:text-gray-800 font-bold text-xl">&times;</button>
                <div class="flex-1">
                    <img id="cartProductImg" src="" alt="Product Image" class="w-full h-64 object-cover rounded">
                </div>
                <div class="flex-1 flex flex-col justify-between">
                    <div>
                        <h2 id="cartProductName" class="text-2xl font-bold mb-2"></h2>
                        <p id="cartProductCategory" class="text-gray-600 mb-1"></p>
                        <p class="text-gray-600 mb-1">Available: <span id="cartProductAvailable"></span></p>
                        <p class="text-gray-800 font-semibold mb-2">Base Price: <span id="cartProductPrice"></span> BDT/unit</p>
                        <label class="block mb-1 font-medium">Quantity:</label>
                        <input type="number" id="cartProductQty" min="1" value="1" class="w-full border p-2 rounded mb-2">
                        <p class="text-gray-800 font-semibold">Total Price: <span id="cartTotalPrice"></span> BDT</p>
                    </div>
                    <button id="confirmAddCart" class="mt-4 bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 w-full">Add to Cart</button>
                </div>
            </div>
        </div>

        <!-- Request Stock Modal -->
        <div id="requestStockModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
            <div class="bg-white rounded-xl shadow-xl max-w-3xl w-full relative p-6 flex gap-6">
                <button onclick="closeModal('requestStockModal')" class="absolute top-3 right-3 text-gray-500 hover:text-gray-800 font-bold text-xl">&times;</button>
                <div class="flex-1">
                    <img id="requestProductImg" src="" alt="Product Image" class="w-full h-64 object-cover rounded">
                </div>
                <div class="flex-1 flex flex-col justify-between">
                    <div>
                        <h2 id="requestProductName" class="text-2xl font-bold mb-2"></h2>
                        <p class="text-gray-800 mb-2">Today's Price: <span id="requestProductPrice"></span> BDT/unit</p>
                        <p class="text-gray-500 mb-4 text-sm">*Price may change when stock is updated</p>
                        <label class="block mb-1 font-medium">Quantity Needed:</label>
                        <input type="number" id="requestProductQty" min="1" value="1" class="w-full border p-2 rounded mb-2">
                    </div>
                    <button id="confirmRequestStock" class="mt-4 bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600 w-full">Request Stock</button>
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
                document.getElementById('cartProductName').innerText = product.name;
                document.getElementById('cartProductCategory').innerText = "Category: " + product.category;
                document.getElementById('cartProductAvailable').innerText = product.total_quantity + " " + product.unit;
                document.getElementById('cartProductPrice').innerText = product.price;
                document.getElementById('cartProductQty').value = 1;
                document.getElementById('cartTotalPrice').innerText = product.price;

                document.getElementById('addCartModal').classList.remove('hidden');

                document.getElementById('cartProductQty').oninput = function() {
                    let qty = Math.max(1, Math.min(this.value, product.total_quantity));
                    this.value = qty;
                    document.getElementById('cartTotalPrice').innerText = (qty * product.price).toFixed(2);
                };
            }

            function openRequestStockModal(product) {
                currentProduct = product;
                document.getElementById('requestProductImg').src = '../' + product.img_url;
                document.getElementById('requestProductName').innerText = product.name;
                document.getElementById('requestProductPrice').innerText = product.price;
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
                        alert(data.message);
                        if (data.status === 'success') {
                            closeModal('addCartModal');
                        }
                    })
                    .catch(err => {
                        console.error('Error adding to cart:', err);
                        alert('Error adding to cart: ' + err.message);
                    });
            };

            document.getElementById('confirmRequestStock').onclick = function() {
                if (!currentProduct) {
                    alert('No product selected');
                    return;
                }

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
                        alert(data.message);
                        if (data.status === 'success') {
                            closeModal('requestStockModal');
                        }
                    })
                    .catch(err => {
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
                    const matchesWarehouse = !warehouse || (product.warehouses && product.warehouses.includes(warehouse));
                    const matchesAvailability = !availability ||
                        (availability === 'in_stock' && product.total_quantity > 0) ||
                        (availability === 'out_of_stock' && product.total_quantity === 0);

                    if (matchesSearch && matchesCategory && matchesWarehouse && matchesAvailability) {
                        const div = document.createElement('div');
                        div.className = 'bg-white rounded shadow-md overflow-hidden flex flex-col justify-between';
                        div.innerHTML = `
                            <img src="../${product.img_url}" alt="${product.product_name}" class="w-full h-48 object-cover">
                            <div class="p-4 flex-1 flex flex-col justify-between">
                                <div>
                                    <h3 class="font-bold text-lg mb-1">${product.product_name}</h3>
                                    <p class="text-gray-600 mb-1">Category: ${product.category}</p>
                                    <p class="text-gray-800 font-semibold mb-1">Price: ${product.price} BDT/unit</p>
                                    <p class="text-gray-600 mb-1">Available: ${product.total_quantity} ${product.unit}</p>
                                </div>
                                ${product.total_quantity > 0 ?
                                    `<button class="mt-3 bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600 w-full" onclick='openAddCartModal(${JSON.stringify(product)})'>Add to Cart</button>` :
                                    `<button class="mt-3 bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600 w-full" onclick='openRequestStockModal(${JSON.stringify({
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
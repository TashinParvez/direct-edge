<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "direct-edge";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize session
session_start();
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle receipt generation and stock deduction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_receipt') {
    header('Content-Type: application/json');

    try {
        if (empty($_SESSION['cart'])) {
            echo json_encode(['success' => false, 'message' => 'Cart is empty']);
            exit;
        }

        $conn->autocommit(false); // Start transaction

        // Calculate totals
        $subtotal = 0;
        foreach ($_SESSION['cart'] as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        $tax_amount = $subtotal * 0.00; // No tax for now
        $discount_amount = 0.00; // No discount for now  
        $total_amount = $subtotal + $tax_amount - $discount_amount;

        // Generate simple transaction number
        $transaction_number = 'TXN-' . date('YmdHis') . '-' . rand(100, 999);

        // Process each cart item and deduct from shop_products
        $stock_issues = [];
        $processed_items = [];

        foreach ($_SESSION['cart'] as $product_id => $item) {
            // Check current stock in shop_products (assuming shop_id = 6 for this example)
            $shop_id = 6; // You can make this dynamic based on your requirements

            $stock_check = $conn->prepare("
                SELECT sp.quantity as current_quantity, p.name, sp.selling_price
                FROM shop_products sp
                JOIN products p ON sp.product_id = p.product_id  
                WHERE sp.product_id = ? AND sp.shop_id = ?
            ");
            $stock_check->bind_param("ii", $product_id, $shop_id);
            $stock_check->execute();
            $stock_result = $stock_check->get_result();

            if ($stock_row = $stock_result->fetch_assoc()) {
                $available_stock = $stock_row['current_quantity'];

                if ($available_stock < $item['quantity']) {
                    $stock_issues[] = "Insufficient stock for {$item['name']}. Available: {$available_stock}, Required: {$item['quantity']}";
                    continue;
                } elseif ($available_stock - $item['quantity'] <= 10) {
                    $stock_issues[] = "Warning: {$item['name']} will be low in stock after this sale";
                }
            } else {
                $stock_issues[] = "Product {$item['name']} not found in shop inventory";
                continue;
            }
            $stock_check->close();

            // Deduct stock from shop_products
            $update_stock = $conn->prepare("
                UPDATE shop_products 
                SET quantity = quantity - ? 
                WHERE product_id = ? AND shop_id = ? AND quantity >= ?
            ");
            $update_stock->bind_param("iiii", $item['quantity'], $product_id, $shop_id, $item['quantity']);

            if (!$update_stock->execute() || $conn->affected_rows == 0) {
                throw new Exception("Failed to deduct stock for {$item['name']}");
            }
            $update_stock->close();

            $processed_items[] = $item;
        }

        // If there were any stock issues but we still processed some items, include warnings
        if (!empty($stock_issues) && empty($processed_items)) {
            throw new Exception("Cannot complete transaction: " . implode("; ", $stock_issues));
        }

        $conn->commit(); // Commit the transaction

        // Clear the cart after successful transaction
        $_SESSION['cart'] = [];

        // Generate receipt HTML
        $receipt_html = generateReceiptHTML($transaction_number, $processed_items, $subtotal, $tax_amount, $discount_amount, $total_amount);

        $response = [
            'success' => true,
            'message' => 'Receipt generated successfully',
            'transaction_number' => $transaction_number,
            'receipt_html' => $receipt_html,
            'warnings' => $stock_issues
        ];

        echo json_encode($response);
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Handle AJAX requests for product search and cart management
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'search_products') {
        $search = $_POST['search'] ?? '';
        $shop_id = 6; // You can make this dynamic

        // Search products that are available in shop_products
        $sql = "SELECT p.product_id, p.name, p.category, sp.selling_price as price, p.unit, p.img_url as image_url, sp.quantity as current_quantity
                FROM products p
                JOIN shop_products sp ON p.product_id = sp.product_id
                WHERE (p.name LIKE ? OR p.category LIKE ?) 
                  AND sp.shop_id = ?
                  AND sp.quantity > 0
                ORDER BY p.name 
                LIMIT 20";
        $stmt = $conn->prepare($sql);
        $searchParam = "%$search%";
        $stmt->bind_param("ssi", $searchParam, $searchParam, $shop_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }

        echo json_encode($products);
        exit;
    }

    if ($_POST['action'] === 'get_inventory_status') {
        $shop_id = 6; // You can make this dynamic

        $sql = "SELECT 
                    COUNT(*) as total_products,
                    COUNT(CASE WHEN sp.quantity <= 10 THEN 1 END) as low_stock_count,
                    SUM(sp.quantity * sp.selling_price) as total_inventory_value
                FROM shop_products sp
                JOIN products p ON sp.product_id = p.product_id
                WHERE sp.shop_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $shop_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $status = $result->fetch_assoc();

        echo json_encode($status);
        exit;
    }
}

// Handle cart operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_action'])) {
    $product_id = $_POST['product_id'] ?? 0;
    $action = $_POST['cart_action'];

    if ($action === 'add' && $product_id > 0) {
        $shop_id = 6; // You can make this dynamic

        // Check stock availability before adding to cart
        $stock_sql = "SELECT sp.quantity as current_quantity, p.product_id, p.name, sp.selling_price as price, p.unit, p.img_url as image_url
                      FROM shop_products sp
                      JOIN products p ON sp.product_id = p.product_id  
                      WHERE sp.product_id = ? AND sp.shop_id = ?";
        $stmt = $conn->prepare($stock_sql);
        $stmt->bind_param("ii", $product_id, $shop_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $available_stock = $row['current_quantity'];
            $current_cart_qty = $_SESSION['cart'][$product_id]['quantity'] ?? 0;

            if (($current_cart_qty + 1) <= $available_stock) {
                if (isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id]['quantity']++;
                } else {
                    $_SESSION['cart'][$product_id] = [
                        'product_id' => $row['product_id'],
                        'name' => $row['name'],
                        'price' => $row['price'],
                        'unit' => $row['unit'],
                        'image_url' => $row['image_url'],
                        'quantity' => 1,
                        'available_stock' => $available_stock
                    ];
                }
            } else {
                $_SESSION['cart_message'] = "Cannot add more. Only {$available_stock} items available in stock.";
            }
        } else {
            $_SESSION['cart_message'] = "Product not available in this shop.";
        }
        $stmt->close();
    } elseif ($action === 'remove' && $product_id > 0) {
        if (isset($_SESSION['cart'][$product_id])) {
            if ($_SESSION['cart'][$product_id]['quantity'] > 1) {
                $_SESSION['cart'][$product_id]['quantity']--;
            } else {
                unset($_SESSION['cart'][$product_id]);
            }
        }
    } elseif ($action === 'clear') {
        $_SESSION['cart'] = [];
    }

    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Function to generate receipt HTML
function generateReceiptHTML($transaction_number, $items, $subtotal, $tax_amount, $discount_amount, $total_amount)
{
    $receipt_html = '<div class="receipt-container">';
    $receipt_html .= '<div class="text-center border-b pb-4 mb-4">';
    $receipt_html .= '<h2 class="text-xl font-bold">DIRECTEDGE STORE</h2>';
    $receipt_html .= '<p class="text-sm text-gray-600">Receipt #: ' . htmlspecialchars($transaction_number) . '</p>';
    $receipt_html .= '<p class="text-sm text-gray-600">Date: ' . date('Y-m-d H:i:s') . '</p>';
    $receipt_html .= '</div>';

    $receipt_html .= '<div class="space-y-2 mb-4">';
    foreach ($items as $item) {
        $line_total = $item['price'] * $item['quantity'];
        $receipt_html .= '<div class="flex justify-between text-sm">';
        $receipt_html .= '<span>' . htmlspecialchars($item['name']) . ' (' . $item['quantity'] . 'x)</span>';
        $receipt_html .= '<span>৳' . number_format($line_total, 2) . '</span>';
        $receipt_html .= '</div>';
    }
    $receipt_html .= '</div>';

    $receipt_html .= '<div class="border-t pt-2 space-y-1">';
    $receipt_html .= '<div class="flex justify-between text-sm">';
    $receipt_html .= '<span>Subtotal:</span>';
    $receipt_html .= '<span>৳' . number_format($subtotal, 2) . '</span>';
    $receipt_html .= '</div>';

    if ($tax_amount > 0) {
        $receipt_html .= '<div class="flex justify-between text-sm">';
        $receipt_html .= '<span>Tax:</span>';
        $receipt_html .= '<span>৳' . number_format($tax_amount, 2) . '</span>';
        $receipt_html .= '</div>';
    }

    if ($discount_amount > 0) {
        $receipt_html .= '<div class="flex justify-between text-sm text-red-600">';
        $receipt_html .= '<span>Discount:</span>';
        $receipt_html .= '<span>-৳' . number_format($discount_amount, 2) . '</span>';
        $receipt_html .= '</div>';
    }

    $receipt_html .= '<div class="flex justify-between font-bold border-t pt-1">';
    $receipt_html .= '<span>TOTAL:</span>';
    $receipt_html .= '<span>৳' . number_format($total_amount, 2) . '</span>';
    $receipt_html .= '</div>';
    $receipt_html .= '</div>';

    $receipt_html .= '<div class="text-center text-xs text-gray-500 mt-4">';
    $receipt_html .= '<p>Thank you for your purchase!</p>';
    $receipt_html .= '<p>Visit us again soon</p>';
    $receipt_html .= '</div>';

    $receipt_html .= '</div>';

    return $receipt_html;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Generate Receipt - Stock Integrated</title>
    <link rel="icon" type="image/x-icon" href="../Logo/LogoBG.png">
    <link rel="stylesheet" href="../Include/sidebar.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
    .cart-item {
        transition: all 0.3s ease;
    }

    .cart-item:hover {
        background-color: #f9fafb;
    }

    .modal {
        backdrop-filter: blur(4px);
    }

    .search-result:hover {
        background-color: #f3f4f6;
    }

    .stock-indicator {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-right: 5px;
    }

    .stock-high {
        background-color: #10b981;
    }

    .stock-medium {
        background-color: #f59e0b;
    }

    .stock-low {
        background-color: #ef4444;
    }

    .receipt-container {
        font-family: 'Courier New', monospace;
        max-width: 300px;
        margin: 0 auto;
    }

    @media print {
        .no-print {
            display: none !important;
        }

        .receipt-container {
            font-size: 12px;
        }
    }
    </style>
</head>

<body class="bg-gray-100">
    <?php include '../Include/Sidebar.php'; ?>

    <section class="home-section p-0">
        <div class="flex justify-between items-center p-4">
            <h1 class="text-2xl font-bold">Generate Receipt (Stock Integrated)</h1>
            <button onclick="showInventoryStatus()"
                class="bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600">
                <i class='bx bx-package'></i> Stock Status
            </button>
        </div>

        <?php if (isset($_SESSION['cart_message'])): ?>
        <div class="mx-4 mb-4 bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
            <?php echo $_SESSION['cart_message'];
                unset($_SESSION['cart_message']); ?>
        </div>
        <?php endif; ?>

        <div class="container mx-auto my-3">
            <!-- Cart Items -->
            <?php if (!empty($_SESSION['cart'])): ?>
            <?php
                $total = 0;
                foreach ($_SESSION['cart'] as $item):
                    $subtotal = $item['price'] * $item['quantity'];
                    $total += $subtotal;
                    $stock_class = 'stock-high';
                    if (isset($item['available_stock'])) {
                        if ($item['available_stock'] <= 10) $stock_class = 'stock-low';
                        elseif ($item['available_stock'] <= 30) $stock_class = 'stock-medium';
                    }
                ?>
            <div class="bg-white shadow-md rounded-lg mb-3 flex cart-item"
                data-product="<?php echo $item['product_id']; ?>">
                <div class="w-1/6 p-2">
                    <img src="<?php echo htmlspecialchars($item['image_url'] ?: '../assets/products-image/default.jpg'); ?>"
                        class="w-full h-48 object-cover rounded" alt="<?php echo htmlspecialchars($item['name']); ?>">
                </div>
                <div class="w-4/6 flex flex-col justify-center p-4">
                    <h5 class="text-xl font-semibold">
                        <span class="stock-indicator <?php echo $stock_class; ?>"></span>
                        <?php echo htmlspecialchars($item['name']); ?>
                    </h5>
                    <p class="text-gray-600">Price: ৳<?php echo number_format($item['price'], 2); ?> per
                        <?php echo htmlspecialchars($item['unit']); ?></p>
                    <p class="counter-price font-bold text-lg">৳<?php echo number_format($subtotal, 2); ?></p>
                    <?php if (isset($item['available_stock'])): ?>
                    <p class="text-xs text-gray-500">Stock Available: <?php echo $item['available_stock']; ?></p>
                    <?php endif; ?>
                </div>
                <div class="w-1/6 flex flex-col items-center justify-center">
                    <div class="flex items-center space-x-2 mb-2">
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                            <input type="hidden" name="cart_action" value="remove">
                            <button type="submit"
                                class="decrease-btn bg-red-200 hover:bg-red-300 px-3 py-1 rounded">-</button>
                        </form>
                        <div class="counter text-lg font-semibold"><?php echo $item['quantity']; ?></div>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                            <input type="hidden" name="cart_action" value="add">
                            <button type="submit"
                                class="increase-btn bg-green-200 hover:bg-green-300 px-3 py-1 rounded">+</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Total Section -->
            <div class="bg-white shadow-md rounded-lg mb-3 p-4">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-bold">Total: ৳<?php echo number_format($total, 2); ?></h3>
                    <div class="space-x-2">
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="cart_action" value="clear">
                            <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Clear
                                Cart</button>
                        </form>
                        <button class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600"
                            onclick="generateReceipt()">Generate Receipt & Process Sale</button>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="bg-white shadow-md rounded-lg p-4 text-center">
                <p class="text-gray-600">Your cart is empty. Add products using the buttons below.</p>
            </div>
            <?php endif; ?>
        </div>

        <div class="container mx-auto my-3 flex space-x-4">
            <button type="button" class="bg-gray-200 px-4 py-2 rounded hover:bg-gray-300" onclick="openManualModal()">
                <i class='bx bx-search'></i> Add Product Manually
            </button>
            <button type="button" class="scan-btn bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                <i class='bx bx-scan'></i> Scan to Add Product
            </button>
        </div>
    </section>

    <!-- Manual Product Addition Modal -->
    <div id="manual-modal"
        class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden modal">
        <div class="bg-white p-6 rounded-lg max-w-4xl w-full mx-4 max-h-96 overflow-hidden">
            <h3 class="text-xl font-bold mb-4">Add Product Manually</h3>
            <div class="mb-4">
                <input type="text" id="product-search" placeholder="Search for products in shop inventory..."
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div id="search-results" class="max-h-64 overflow-y-auto mb-4">
                <!-- Search results will be populated here -->
            </div>
            <div class="flex justify-end space-x-2">
                <button onclick="closeManualModal()"
                    class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Webcam Scanning Modal -->
    <div id="webcam-modal"
        class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden modal">
        <div class="bg-white p-4 rounded-lg max-w-md w-full mx-4">
            <h3 class="text-lg font-bold mb-2">Scanning for Products...</h3>
            <video id="webcam-video" autoplay class="w-full max-h-64 object-cover rounded mb-2"
                style="background: black;"></video>
            <div id="detection-status" class="text-sm text-gray-600 mb-2">Hold a product in view...</div>
            <button id="stop-scan-btn" class="bg-red-500 text-white px-4 py-2 rounded w-full">Stop Scan</button>
        </div>
    </div>

    <!-- Receipt Modal -->
    <div id="receipt-modal"
        class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden modal">
        <div class="bg-white p-6 rounded-lg max-w-md w-full mx-4">
            <div id="receipt-content">
                <!-- Receipt content will be generated here -->
            </div>
            <div class="flex justify-end space-x-2 mt-4 no-print">
                <button onclick="printReceipt()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                    <i class='bx bx-printer'></i> Print
                </button>
                <button onclick="closeReceiptModal()"
                    class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Close</button>
            </div>
        </div>
    </div>

    <!-- Inventory Status Modal -->
    <div id="inventory-modal"
        class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden modal">
        <div class="bg-white p-6 rounded-lg max-w-md w-full mx-4">
            <h3 class="text-lg font-bold mb-4">Store Inventory Status</h3>
            <div id="inventory-content">
                <!-- Inventory status will be loaded here -->
            </div>
            <div class="flex justify-end space-x-2 mt-4">
                <button onclick="closeInventoryModal()"
                    class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Close</button>
            </div>
        </div>
    </div>

    <script>
    // Manual Product Addition
    function openManualModal() {
        document.getElementById('manual-modal').classList.remove('hidden');
        document.getElementById('product-search').focus();
        searchProducts(''); // Load all products initially
    }

    function closeManualModal() {
        document.getElementById('manual-modal').classList.add('hidden');
        document.getElementById('search-results').innerHTML = '';
        document.getElementById('product-search').value = '';
    }

    function searchProducts(query) {
        fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=search_products&search=' + encodeURIComponent(query)
            })
            .then(response => response.json())
            .then(products => {
                const resultsDiv = document.getElementById('search-results');
                resultsDiv.innerHTML = '';

                if (products.length === 0) {
                    resultsDiv.innerHTML =
                        '<p class="text-gray-500 text-center">No products found in shop inventory</p>';
                    return;
                }

                products.forEach(product => {
                    let stockClass = 'stock-high';
                    if (product.current_quantity <= 10) stockClass = 'stock-low';
                    else if (product.current_quantity <= 30) stockClass = 'stock-medium';

                    const productDiv = document.createElement('div');
                    productDiv.className =
                        'flex items-center p-3 border-b hover:bg-gray-50 cursor-pointer search-result';
                    productDiv.innerHTML = `
                    <img src="${product.image_url || '../assets/products-image/default.jpg'}" 
                         class="w-12 h-12 object-cover rounded mr-3" alt="${product.name}">
                    <div class="flex-1">
                        <h4 class="font-semibold">
                            <span class="stock-indicator ${stockClass}"></span>
                            ${product.name}
                        </h4>
                        <p class="text-sm text-gray-600">${product.category} - ৳${parseFloat(product.price).toFixed(2)} per ${product.unit}</p>
                        <p class="text-xs text-gray-500">Stock: ${product.current_quantity} available</p>
                    </div>
                    <button onclick="addToCart(${product.product_id})" 
                            class="bg-blue-500 text-white px-3 py-1 rounded text-sm hover:bg-blue-600">Add</button>
                `;
                    resultsDiv.appendChild(productDiv);
                });
            })
            .catch(error => {
                console.error('Error searching products:', error);
            });
    }

    function addToCart(productId) {
        const form = document.createElement('form');
        form.method = 'post';
        form.innerHTML = `
            <input type="hidden" name="product_id" value="${productId}">
            <input type="hidden" name="cart_action" value="add">
        `;
        document.body.appendChild(form);
        form.submit();
    }

    // Generate Receipt with Stock Deduction
    function generateReceipt() {
        if (!confirm('This will process the sale and deduct items from inventory. Continue?')) {
            return;
        }

        fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=generate_receipt'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('receipt-content').innerHTML = data.receipt_html;
                    document.getElementById('receipt-modal').classList.remove('hidden');

                    // Show warnings if any
                    if (data.warnings && data.warnings.length > 0) {
                        alert('Transaction completed with warnings:\n' + data.warnings.join('\n'));
                    }

                    // Reload page to update cart and stock display
                    setTimeout(() => {
                        location.reload();
                    }, 3000);
                } else {
                    alert('Error generating receipt: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error generating receipt. Please check the console.');
            });
    }

    function closeReceiptModal() {
        document.getElementById('receipt-modal').classList.add('hidden');
    }

    function printReceipt() {
        const printContent = document.getElementById('receipt-content').innerHTML;
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Receipt</title>
                    <style>
                        body { font-family: 'Courier New', monospace; max-width: 300px; margin: 0 auto; font-size: 12px; }
                        .receipt-container { font-family: 'Courier New', monospace; }
                        .text-center { text-align: center; }
                        .text-xl { font-size: 1.25rem; }
                        .text-sm { font-size: 0.875rem; }
                        .text-xs { font-size: 0.75rem; }
                        .font-bold { font-weight: bold; }
                        .border-b { border-bottom: 1px solid #ccc; }
                        .border-t { border-top: 1px solid #ccc; }
                        .pb-4 { padding-bottom: 1rem; }
                        .pt-2 { padding-top: 0.5rem; }
                        .pt-1 { padding-top: 0.25rem; }
                        .mb-4 { margin-bottom: 1rem; }
                        .mt-4 { margin-top: 1rem; }
                        .space-y-2 > * + * { margin-top: 0.5rem; }
                        .space-y-1 > * + * { margin-top: 0.25rem; }
                        .flex { display: flex; }
                        .justify-between { justify-content: space-between; }
                        .text-gray-600 { color: #6b7280; }
                        .text-gray-500 { color: #9ca3af; }
                        .text-red-600 { color: #dc2626; }
                    </style>
                </head>
                <body>
                    ${printContent}
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
        printWindow.close();
    }

    // Inventory Status
    function showInventoryStatus() {
        fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_inventory_status'
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('inventory-content').innerHTML = `
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span>Total Products:</span>
                        <span class="font-semibold">${data.total_products}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Low Stock Items:</span>
                        <span class="font-semibold text-red-600">${data.low_stock_count}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Total Inventory Value:</span>
                        <span class="font-semibold">৳${parseFloat(data.total_inventory_value).toLocaleString()}</span>
                    </div>
                </div>
            `;
                document.getElementById('inventory-modal').classList.remove('hidden');
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }

    function closeInventoryModal() {
        document.getElementById('inventory-modal').classList.add('hidden');
    }

    // Search input event listener
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('product-search');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                searchProducts(this.value);
            });
        }
    });

    // Webcam scanning code
    const scanBtn = document.querySelector('.scan-btn');
    const modal = document.getElementById('webcam-modal');
    const video = document.getElementById('webcam-video');
    const status = document.getElementById('detection-status');
    const stopBtn = document.getElementById('stop-scan-btn');
    const API_KEY = 'u64iBlZU98pNf3NhHccg'; // Your Roboflow API Key

    let stream = null;
    let detectionInterval = null;
    let detected = false;

    // Product mapping for detection - Updated to use actual products from your database
    const productMappings = {
        'lays': 1, // Apple - since you mentioned product_id 18 but your database shows products 1-15
        'chips': 2, // Banana 
        'snack': 3, // Mango
        'apple': 1, // Apple
        'banana': 2, // Banana
        'orange': 4 // Orange
    };

    scanBtn.addEventListener('click', async () => {
        console.log('Starting scan...');
        try {
            modal.classList.remove('hidden');

            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'environment'
                }
            });
            video.srcObject = stream;
            await new Promise(resolve => {
                video.onloadedmetadata = () => {
                    video.play();
                    resolve();
                };
            });

            status.textContent = 'Hold a product in view...';

            detectionInterval = setInterval(async () => {
                if (video.readyState === video.HAVE_ENOUGH_DATA && !detected) {
                    const canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    canvas.getContext('2d').drawImage(video, 0, 0);
                    const imageData = canvas.toDataURL('image/jpeg', 0.8);

                    try {
                        const response = await fetch(
                            'https://detect.roboflow.com/lays-txw7q/1?api_key=' + API_KEY, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: imageData
                            });
                        const predictions = await response.json();

                        if (predictions.predictions && predictions.predictions.length > 0 &&
                            predictions.predictions[0].confidence > 0.5) {

                            const detectedClass = predictions.predictions[0].class
                                .toLowerCase();
                            const productId = productMappings[detectedClass];

                            if (productId) {
                                status.textContent =
                                    `Detected ${predictions.predictions[0].class}! Adding to cart...`;
                                detected = true;

                                // Add to cart
                                const form = document.createElement('form');
                                form.method = 'post';
                                form.innerHTML = `
                                    <input type="hidden" name="product_id" value="${productId}">
                                    <input type="hidden" name="cart_action" value="add">
                                `;
                                document.body.appendChild(form);

                                setTimeout(() => {
                                    form.submit();
                                }, 1000);
                            } else {
                                status.textContent =
                                    `Detected ${predictions.predictions[0].class} but no matching product found.`;
                            }
                        } else {
                            status.textContent =
                                'No products detected. Try adjusting the angle or lighting.';
                        }
                    } catch (apiError) {
                        console.error('API error:', apiError);
                        status.textContent = 'Error contacting detection API.';
                    }
                }
            }, 1000);

        } catch (error) {
            console.error('Scan error:', error);
            alert(`Error starting scan: ${error.message}`);
            stopScan();
        }
    });

    stopBtn.addEventListener('click', stopScan);

    function stopScan() {
        if (detectionInterval) clearInterval(detectionInterval);
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
        }
        modal.classList.add('hidden');
        detected = false;
        status.textContent = 'Hold a product in view...';
    }

    window.addEventListener('beforeunload', stopScan);
    </script>
</body>

</html>
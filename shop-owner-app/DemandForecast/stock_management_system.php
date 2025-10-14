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

// Handle AJAX requests for stock management
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'get_stock_data') {
        $shop_id = $_POST['shop_id'] ?? 6;

        // Get stock data with product details
        $sql = "SELECT 
                    sp.product_id,
                    p.name,
                    p.category,
                    sp.quantity as current_stock,
                    sp.selling_price,
                    sp.cost_price,
                    p.unit,
                    p.img_url as image_url,
                    CASE 
                        WHEN sp.quantity <= 10 THEN 'critical'
                        WHEN sp.quantity <= 30 THEN 'low'
                        WHEN sp.quantity <= 100 THEN 'medium'
                        ELSE 'high'
                    END as stock_level,
                    (sp.quantity * sp.cost_price) as total_value
                FROM shop_products sp
                JOIN products p ON sp.product_id = p.product_id
                WHERE sp.shop_id = ?
                ORDER BY sp.quantity ASC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $shop_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $stock_data = [];
        while ($row = $result->fetch_assoc()) {
            $stock_data[] = $row;
        }

        echo json_encode($stock_data);
        exit;
    }

    if ($_POST['action'] === 'get_stock_summary') {
        $shop_id = $_POST['shop_id'] ?? 6;

        $sql = "SELECT 
                    COUNT(*) as total_products,
                    COUNT(CASE WHEN sp.quantity <= 10 THEN 1 END) as critical_stock,
                    COUNT(CASE WHEN sp.quantity <= 30 THEN 1 END) as low_stock,
                    COUNT(CASE WHEN sp.quantity > 100 THEN 1 END) as high_stock,
                    SUM(sp.quantity) as total_quantity,
                    SUM(sp.quantity * sp.cost_price) as total_cost_value,
                    SUM(sp.quantity * sp.selling_price) as total_selling_value,
                    COUNT(CASE WHEN sp.quantity = 0 THEN 1 END) as out_of_stock
                FROM shop_products sp
                WHERE sp.shop_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $shop_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $summary = $result->fetch_assoc();

        echo json_encode($summary);
        exit;
    }

    if ($_POST['action'] === 'adjust_stock') {
        $product_id = $_POST['product_id'] ?? 0;
        $adjustment_type = $_POST['adjustment_type'] ?? 'in'; // 'in' or 'out'
        $quantity = intval($_POST['quantity'] ?? 0);
        $reason = $_POST['reason'] ?? '';
        $shop_id = $_POST['shop_id'] ?? 6;

        if ($product_id > 0 && $quantity > 0) {
            try {
                $conn->autocommit(false);

                // Get current stock
                $current_stock_sql = "SELECT quantity, cost_price FROM shop_products WHERE product_id = ? AND shop_id = ?";
                $stmt = $conn->prepare($current_stock_sql);
                $stmt->bind_param("ii", $product_id, $shop_id);
                $stmt->execute();
                $current_result = $stmt->get_result();

                if ($current_row = $current_result->fetch_assoc()) {
                    $current_quantity = $current_row['quantity'];
                    $cost_price = $current_row['cost_price'];

                    // Calculate new quantity
                    $new_quantity = $adjustment_type === 'in' ? 
                        $current_quantity + $quantity : 
                        max(0, $current_quantity - $quantity);

                    // Update stock
                    $update_sql = "UPDATE shop_products SET quantity = ? WHERE product_id = ? AND shop_id = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    $update_stmt->bind_param("iii", $new_quantity, $product_id, $shop_id);
                    $update_stmt->execute();

                    // Log the adjustment in stock_movements table (create if not exists)
                    $log_sql = "INSERT INTO stock_movements (product_id, shop_id, movement_type, quantity, previous_stock, new_stock, reason, created_at) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
                    $log_stmt = $conn->prepare($log_sql);
                    $movement_quantity = $adjustment_type === 'out' ? -$quantity : $quantity;
                    $log_stmt->bind_param("iisissis", $product_id, $shop_id, $adjustment_type, $movement_quantity, $current_quantity, $new_quantity, $reason);

                    // Create table if it doesn't exist
                    $create_table_sql = "CREATE TABLE IF NOT EXISTS stock_movements (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        product_id INT NOT NULL,
                        shop_id INT NOT NULL,
                        movement_type ENUM('in', 'out', 'adjustment') NOT NULL,
                        quantity INT NOT NULL,
                        previous_stock INT NOT NULL,
                        new_stock INT NOT NULL,
                        reason TEXT,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_product_shop (product_id, shop_id),
                        INDEX idx_created_at (created_at)
                    )";
                    $conn->query($create_table_sql);

                    $log_stmt->execute();

                    $conn->commit();

                    echo json_encode([
                        'success' => true, 
                        'message' => 'Stock adjusted successfully',
                        'new_quantity' => $new_quantity,
                        'adjustment' => $movement_quantity
                    ]);
                } else {
                    throw new Exception("Product not found in shop inventory");
                }

            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid product ID or quantity']);
        }
        exit;
    }

    if ($_POST['action'] === 'get_stock_movements') {
        $product_id = $_POST['product_id'] ?? 0;
        $shop_id = $_POST['shop_id'] ?? 6;
        $limit = $_POST['limit'] ?? 50;

        $sql = "SELECT 
                    sm.id,
                    sm.movement_type,
                    sm.quantity,
                    sm.previous_stock,
                    sm.new_stock,
                    sm.reason,
                    sm.created_at,
                    p.name as product_name
                FROM stock_movements sm
                JOIN products p ON sm.product_id = p.product_id
                WHERE sm.shop_id = ?";

        $params = [$shop_id];
        $types = "i";

        if ($product_id > 0) {
            $sql .= " AND sm.product_id = ?";
            $params[] = $product_id;
            $types .= "i";
        }

        $sql .= " ORDER BY sm.created_at DESC LIMIT ?";
        $params[] = $limit;
        $types .= "i";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $movements = [];
        while ($row = $result->fetch_assoc()) {
            $movements[] = $row;
        }

        echo json_encode($movements);
        exit;
    }
}

// Get initial data for page load
$shop_id = 6; // This can be dynamic
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Management & Tracking</title>
    <link rel="icon" type="image/x-icon" href="../Logo/LogoBG.png">
    <link rel="stylesheet" href="../Include/sidebar.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .stock-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .stock-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .modal {
            backdrop-filter: blur(4px);
        }

        .stock-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .stock-critical { background-color: #ef4444; }
        .stock-low { background-color: #f59e0b; }
        .stock-medium { background-color: #3b82f6; }
        .stock-high { background-color: #10b981; }

        .progress-bar {
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .movement-item {
            border-left: 4px solid transparent;
        }

        .movement-in { border-left-color: #10b981; }
        .movement-out { border-left-color: #ef4444; }
        .movement-adjustment { border-left-color: #3b82f6; }

        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>

<body class="bg-gray-100">
    <?php include '../Include/SidebarAgent.php'; ?>

    <section class="home-section p-0">
        <!-- Header -->
        <div class="flex justify-between items-center p-6 bg-white shadow-sm">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Stock Management & Tracking</h1>
                <p class="text-gray-600 mt-1">Monitor inventory levels, track movements, and manage stock adjustments</p>
            </div>
            <div class="flex space-x-3">
                <button onclick="refreshData()" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 flex items-center">
                    <i class='bx bx-refresh mr-2'></i> Refresh
                </button>
                <button onclick="openStockAdjustmentModal()" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 flex items-center">
                    <i class='bx bx-plus mr-2'></i> Adjust Stock
                </button>
                <button onclick="exportStockReport()" class="bg-purple-500 text-white px-4 py-2 rounded-lg hover:bg-purple-600 flex items-center">
                    <i class='bx bx-download mr-2'></i> Export Report
                </button>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Products</p>
                            <p id="total-products" class="text-3xl font-bold text-gray-900">-</p>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-full">
                            <i class='bx bx-package text-2xl text-blue-600'></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Critical Stock</p>
                            <p id="critical-stock" class="text-3xl font-bold text-red-600">-</p>
                        </div>
                        <div class="p-3 bg-red-100 rounded-full">
                            <i class='bx bx-error text-2xl text-red-600'></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Total Value</p>
                            <p id="total-value" class="text-3xl font-bold text-green-600">৳-</p>
                        </div>
                        <div class="p-3 bg-green-100 rounded-full">
                            <i class='bx bx-dollar text-2xl text-green-600'></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Out of Stock</p>
                            <p id="out-of-stock" class="text-3xl font-bold text-orange-600">-</p>
                        </div>
                        <div class="p-3 bg-orange-100 rounded-full">
                            <i class='bx bx-x-circle text-2xl text-orange-600'></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200 mb-6">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex-1 min-w-64">
                        <input type="text" id="search-products" placeholder="Search products by name or category..." 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <select id="stock-level-filter" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Stock Levels</option>
                        <option value="critical">Critical (≤10)</option>
                        <option value="low">Low (≤30)</option>
                        <option value="medium">Medium (≤100)</option>
                        <option value="high">High (>100)</option>
                    </select>
                    <select id="category-filter" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Categories</option>
                        <!-- Categories will be populated dynamically -->
                    </select>
                    <button onclick="clearFilters()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                        Clear Filters
                    </button>
                </div>
            </div>

            <!-- Stock Items Grid -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-800">Current Stock Inventory</h2>
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center space-x-2 text-sm">
                                <span class="stock-indicator stock-critical"></span>
                                <span>Critical</span>
                                <span class="stock-indicator stock-low"></span>
                                <span>Low</span>
                                <span class="stock-indicator stock-medium"></span>
                                <span>Medium</span>
                                <span class="stock-indicator stock-high"></span>
                                <span>High</span>
                            </div>
                            <button onclick="toggleView()" id="view-toggle" class="text-blue-600 hover:text-blue-800">
                                <i class='bx bx-list text-xl'></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div id="stock-container" class="p-6">
                    <div class="text-center py-12">
                        <i class='bx bx-loader-alt bx-spin text-4xl text-gray-400 mb-4'></i>
                        <p class="text-gray-600">Loading stock data...</p>
                    </div>
                </div>
            </div>

            <!-- Recent Stock Movements -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 mt-6">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-800">Recent Stock Movements</h2>
                        <button onclick="viewAllMovements()" class="text-blue-600 hover:text-blue-800 text-sm">
                            View All →
                        </button>
                    </div>
                </div>

                <div id="movements-container" class="p-6">
                    <div class="text-center py-8">
                        <i class='bx bx-loader-alt bx-spin text-3xl text-gray-400 mb-3'></i>
                        <p class="text-gray-600">Loading movements...</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stock Adjustment Modal -->
    <div id="stock-adjustment-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden modal">
        <div class="bg-white p-6 rounded-lg max-w-md w-full mx-4">
            <h3 class="text-xl font-bold mb-4">Stock Adjustment</h3>
            <form id="stock-adjustment-form">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Product</label>
                    <select id="adjustment-product" name="product_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">Select Product</option>
                        <!-- Products will be populated dynamically -->
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Stock</label>
                    <div id="current-stock-display" class="px-3 py-2 bg-gray-100 rounded-lg text-gray-700">
                        Select a product to view current stock
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Adjustment Type</label>
                    <div class="flex space-x-4">
                        <label class="flex items-center">
                            <input type="radio" name="adjustment_type" value="in" class="mr-2" required>
                            <span class="text-green-600">Stock In (+)</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="adjustment_type" value="out" class="mr-2" required>
                            <span class="text-red-600">Stock Out (-)</span>
                        </label>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                    <input type="number" name="quantity" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Reason</label>
                    <textarea name="reason" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Enter reason for stock adjustment..."></textarea>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeStockAdjustmentModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                        Apply Adjustment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Stock Details Modal -->
    <div id="stock-details-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden modal">
        <div class="bg-white p-6 rounded-lg max-w-2xl w-full mx-4 max-h-96 overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold">Stock Details</h3>
                <button onclick="closeStockDetailsModal()" class="text-gray-500 hover:text-gray-700">
                    <i class='bx bx-x text-2xl'></i>
                </button>
            </div>

            <!-- Product Info -->
            <div id="product-details-content">
                <!-- Content will be populated dynamically -->
            </div>

            <!-- Stock Movement History for this product -->
            <div class="mt-6">
                <h4 class="text-lg font-semibold mb-3">Recent Movements</h4>
                <div id="product-movements-list" class="max-h-64 overflow-y-auto">
                    <!-- Movements will be populated dynamically -->
                </div>
            </div>
        </div>
    </div>

    <!-- All Movements Modal -->
    <div id="all-movements-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden modal">
        <div class="bg-white p-6 rounded-lg max-w-4xl w-full mx-4 max-h-96 overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold">All Stock Movements</h3>
                <button onclick="closeAllMovementsModal()" class="text-gray-500 hover:text-gray-700">
                    <i class='bx bx-x text-2xl'></i>
                </button>
            </div>

            <div id="all-movements-content">
                <!-- All movements will be populated here -->
            </div>
        </div>
    </div>

    <script>
        let stockData = [];
        let currentView = 'grid'; // 'grid' or 'list'

        document.addEventListener('DOMContentLoaded', function() {
            loadInitialData();
            setupEventListeners();
        });

        function setupEventListeners() {
            // Search functionality
            document.getElementById('search-products').addEventListener('input', filterStock);
            document.getElementById('stock-level-filter').addEventListener('change', filterStock);
            document.getElementById('category-filter').addEventListener('change', filterStock);

            // Stock adjustment form
            document.getElementById('stock-adjustment-form').addEventListener('submit', handleStockAdjustment);
            document.getElementById('adjustment-product').addEventListener('change', updateCurrentStockDisplay);
        }

        function loadInitialData() {
            loadStockSummary();
            loadStockData();
            loadRecentMovements();
            loadProductsForAdjustment();
        }

        function loadStockSummary() {
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_stock_summary&shop_id=6'
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('total-products').textContent = data.total_products || 0;
                document.getElementById('critical-stock').textContent = data.critical_stock || 0;
                document.getElementById('total-value').textContent = '৳' + (parseFloat(data.total_cost_value || 0).toLocaleString());
                document.getElementById('out-of-stock').textContent = data.out_of_stock || 0;
            })
            .catch(error => console.error('Error loading summary:', error));
        }

        function loadStockData() {
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_stock_data&shop_id=6'
            })
            .then(response => response.json())
            .then(data => {
                stockData = data;
                populateCategories();
                renderStockItems();
            })
            .catch(error => {
                console.error('Error loading stock data:', error);
                document.getElementById('stock-container').innerHTML = 
                    '<div class="text-center py-12"><p class="text-red-600">Error loading stock data</p></div>';
            });
        }

        function populateCategories() {
            const categories = [...new Set(stockData.map(item => item.category))];
            const categoryFilter = document.getElementById('category-filter');

            // Clear existing options except "All Categories"
            categoryFilter.innerHTML = '<option value="">All Categories</option>';

            categories.forEach(category => {
                const option = document.createElement('option');
                option.value = category;
                option.textContent = category;
                categoryFilter.appendChild(option);
            });
        }

        function renderStockItems() {
            const container = document.getElementById('stock-container');

            if (stockData.length === 0) {
                container.innerHTML = '<div class="text-center py-12"><p class="text-gray-600">No stock items found</p></div>';
                return;
            }

            if (currentView === 'grid') {
                renderGridView(container);
            } else {
                renderListView(container);
            }
        }

        function renderGridView(container) {
            let html = '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">';

            stockData.forEach(item => {
                const stockPercentage = Math.min((item.current_stock / 100) * 100, 100);

                html += `
                    <div class="stock-card bg-white rounded-lg border border-gray-200 p-4 hover:shadow-lg transition-all duration-300" 
                         onclick="showStockDetails(${item.product_id})">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1">
                                <div class="flex items-center mb-2">
                                    <span class="stock-indicator stock-${item.stock_level}"></span>
                                    <h3 class="font-semibold text-gray-800 truncate">${item.name}</h3>
                                </div>
                                <p class="text-sm text-gray-600 mb-1">${item.category}</p>
                                <p class="text-xs text-gray-500">${item.unit}</p>
                            </div>
                            <img src="${item.image_url || '../assets/products-image/default.jpg'}" 
                                 class="w-12 h-12 object-cover rounded-lg ml-2" alt="${item.name}">
                        </div>

                        <div class="mb-3">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-sm font-medium text-gray-700">Stock Level</span>
                                <span class="text-sm font-bold ${getStockColorClass(item.stock_level)}">${item.current_stock}</span>
                            </div>
                            <div class="progress-bar bg-gray-200">
                                <div class="h-full ${getStockBgClass(item.stock_level)} transition-all duration-300" 
                                     style="width: ${stockPercentage}%"></div>
                            </div>
                        </div>

                        <div class="flex justify-between items-center text-sm">
                            <div>
                                <p class="text-gray-600">Cost: ৳${parseFloat(item.cost_price || 0).toFixed(2)}</p>
                                <p class="text-gray-600">Sell: ৳${parseFloat(item.selling_price || 0).toFixed(2)}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-800">৳${parseFloat(item.total_value || 0).toFixed(2)}</p>
                                <p class="text-xs text-gray-500">Total Value</p>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            container.innerHTML = html;
        }

        function renderListView(container) {
            let html = `
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Selling Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Value</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
            `;

            stockData.forEach(item => {
                html += `
                    <tr class="hover:bg-gray-50 cursor-pointer" onclick="showStockDetails(${item.product_id})">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <img src="${item.image_url || '../assets/products-image/default.jpg'}" 
                                     class="w-10 h-10 object-cover rounded-lg mr-3" alt="${item.name}">
                                <div>
                                    <div class="flex items-center">
                                        <span class="stock-indicator stock-${item.stock_level}"></span>
                                        <span class="text-sm font-medium text-gray-900">${item.name}</span>
                                    </div>
                                    <span class="text-xs text-gray-500">${item.unit}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.category}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-bold ${getStockColorClass(item.stock_level)}">${item.current_stock}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">৳${parseFloat(item.cost_price || 0).toFixed(2)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">৳${parseFloat(item.selling_price || 0).toFixed(2)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">৳${parseFloat(item.total_value || 0).toFixed(2)}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <button onclick="event.stopPropagation(); quickAdjust(${item.product_id})" 
                                    class="text-blue-600 hover:text-blue-900 mr-2">Adjust</button>
                            <button onclick="event.stopPropagation(); showStockDetails(${item.product_id})" 
                                    class="text-gray-600 hover:text-gray-900">View</button>
                        </td>
                    </tr>
                `;
            });

            html += '</tbody></table></div>';
            container.innerHTML = html;
        }

        function getStockColorClass(level) {
            switch(level) {
                case 'critical': return 'text-red-600';
                case 'low': return 'text-orange-600';
                case 'medium': return 'text-blue-600';
                case 'high': return 'text-green-600';
                default: return 'text-gray-600';
            }
        }

        function getStockBgClass(level) {
            switch(level) {
                case 'critical': return 'bg-red-500';
                case 'low': return 'bg-orange-500';
                case 'medium': return 'bg-blue-500';
                case 'high': return 'bg-green-500';
                default: return 'bg-gray-500';
            }
        }

        function toggleView() {
            currentView = currentView === 'grid' ? 'list' : 'grid';
            const toggleBtn = document.getElementById('view-toggle');
            toggleBtn.innerHTML = currentView === 'grid' ? 
                '<i class="bx bx-list text-xl"></i>' : 
                '<i class="bx bx-grid-alt text-xl"></i>';
            renderStockItems();
        }

        function filterStock() {
            const searchTerm = document.getElementById('search-products').value.toLowerCase();
            const stockLevelFilter = document.getElementById('stock-level-filter').value;
            const categoryFilter = document.getElementById('category-filter').value;

            // Reset to original data and then filter
            let filteredData = [...stockData];

            if (searchTerm) {
                filteredData = filteredData.filter(item => 
                    item.name.toLowerCase().includes(searchTerm) || 
                    item.category.toLowerCase().includes(searchTerm)
                );
            }

            if (stockLevelFilter) {
                filteredData = filteredData.filter(item => item.stock_level === stockLevelFilter);
            }

            if (categoryFilter) {
                filteredData = filteredData.filter(item => item.category === categoryFilter);
            }

            // Temporarily replace stockData for rendering
            const originalData = stockData;
            stockData = filteredData;
            renderStockItems();
            stockData = originalData;
        }

        function clearFilters() {
            document.getElementById('search-products').value = '';
            document.getElementById('stock-level-filter').value = '';
            document.getElementById('category-filter').value = '';
            renderStockItems();
        }

        function refreshData() {
            loadInitialData();
        }

        // Stock Adjustment Functions
        function openStockAdjustmentModal() {
            document.getElementById('stock-adjustment-modal').classList.remove('hidden');
        }

        function closeStockAdjustmentModal() {
            document.getElementById('stock-adjustment-modal').classList.add('hidden');
            document.getElementById('stock-adjustment-form').reset();
            document.getElementById('current-stock-display').textContent = 'Select a product to view current stock';
        }

        function loadProductsForAdjustment() {
            const select = document.getElementById('adjustment-product');
            select.innerHTML = '<option value="">Select Product</option>';

            stockData.forEach(item => {
                const option = document.createElement('option');
                option.value = item.product_id;
                option.textContent = `${item.name} (${item.category})`;
                option.dataset.currentStock = item.current_stock;
                select.appendChild(option);
            });
        }

        function updateCurrentStockDisplay() {
            const select = document.getElementById('adjustment-product');
            const display = document.getElementById('current-stock-display');

            if (select.value) {
                const selectedOption = select.options[select.selectedIndex];
                const currentStock = selectedOption.dataset.currentStock;
                display.textContent = `Current Stock: ${currentStock} units`;
            } else {
                display.textContent = 'Select a product to view current stock';
            }
        }

        function handleStockAdjustment(e) {
            e.preventDefault();

            const formData = new FormData(e.target);
            formData.append('action', 'adjust_stock');
            formData.append('shop_id', '6');

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Stock adjusted successfully!');
                    closeStockAdjustmentModal();
                    refreshData();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error adjusting stock:', error);
                alert('Error adjusting stock. Please try again.');
            });
        }

        function quickAdjust(productId) {
            document.getElementById('adjustment-product').value = productId;
            updateCurrentStockDisplay();
            openStockAdjustmentModal();
        }

        // Stock Details Functions
        function showStockDetails(productId) {
            const product = stockData.find(item => item.product_id == productId);
            if (!product) return;

            const modal = document.getElementById('stock-details-modal');
            const content = document.getElementById('product-details-content');

            content.innerHTML = `
                <div class="flex items-start space-x-4 mb-6">
                    <img src="${product.image_url || '../assets/products-image/default.jpg'}" 
                         class="w-24 h-24 object-cover rounded-lg" alt="${product.name}">
                    <div class="flex-1">
                        <h4 class="text-xl font-semibold text-gray-800 mb-2">${product.name}</h4>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-gray-600">Category: <span class="font-medium">${product.category}</span></p>
                                <p class="text-gray-600">Unit: <span class="font-medium">${product.unit}</span></p>
                                <p class="text-gray-600">Stock Level: <span class="stock-indicator stock-${product.stock_level}"></span>
                                   <span class="font-medium ${getStockColorClass(product.stock_level)}">${product.current_stock}</span></p>
                            </div>
                            <div>
                                <p class="text-gray-600">Cost Price: <span class="font-medium">৳${parseFloat(product.cost_price || 0).toFixed(2)}</span></p>
                                <p class="text-gray-600">Selling Price: <span class="font-medium">৳${parseFloat(product.selling_price || 0).toFixed(2)}</span></p>
                                <p class="text-gray-600">Total Value: <span class="font-medium">৳${parseFloat(product.total_value || 0).toFixed(2)}</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            modal.classList.remove('hidden');
            loadProductMovements(productId);
        }

        function closeStockDetailsModal() {
            document.getElementById('stock-details-modal').classList.add('hidden');
        }

        function loadProductMovements(productId) {
            const container = document.getElementById('product-movements-list');
            container.innerHTML = '<div class="text-center py-4"><i class="bx bx-loader-alt bx-spin text-xl text-gray-400"></i></div>';

            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=get_stock_movements&product_id=${productId}&shop_id=6&limit=10`
            })
            .then(response => response.json())
            .then(movements => {
                if (movements.length === 0) {
                    container.innerHTML = '<p class="text-gray-500 text-center py-4">No movements found</p>';
                    return;
                }

                let html = '';
                movements.forEach(movement => {
                    const date = new Date(movement.created_at).toLocaleString();
                    html += `
                        <div class="movement-item movement-${movement.movement_type} bg-gray-50 rounded-lg p-3 mb-2">
                            <div class="flex justify-between items-start">
                                <div>
                                    <p class="font-medium ${movement.movement_type === 'in' ? 'text-green-600' : 'text-red-600'}">
                                        ${movement.movement_type === 'in' ? '+' : ''}${movement.quantity} units
                                    </p>
                                    <p class="text-sm text-gray-600">${movement.reason || 'No reason provided'}</p>
                                </div>
                                <div class="text-right text-sm text-gray-500">
                                    <p>${date}</p>
                                    <p>${movement.previous_stock} → ${movement.new_stock}</p>
                                </div>
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
            })
            .catch(error => {
                console.error('Error loading movements:', error);
                container.innerHTML = '<p class="text-red-500 text-center py-4">Error loading movements</p>';
            });
        }

        // Recent Movements Functions
        function loadRecentMovements() {
            const container = document.getElementById('movements-container');

            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_stock_movements&shop_id=6&limit=5'
            })
            .then(response => response.json())
            .then(movements => {
                if (movements.length === 0) {
                    container.innerHTML = '<p class="text-gray-500 text-center py-8">No recent movements</p>';
                    return;
                }

                let html = '<div class="space-y-3">';
                movements.forEach(movement => {
                    const date = new Date(movement.created_at).toLocaleString();
                    html += `
                        <div class="movement-item movement-${movement.movement_type} bg-gray-50 rounded-lg p-4">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-800">${movement.product_name}</h4>
                                    <p class="text-sm ${movement.movement_type === 'in' ? 'text-green-600' : 'text-red-600'} font-medium">
                                        ${movement.movement_type === 'in' ? '+' : ''}${movement.quantity} units
                                    </p>
                                    <p class="text-sm text-gray-600">${movement.reason || 'No reason provided'}</p>
                                </div>
                                <div class="text-right text-sm text-gray-500">
                                    <p>${date}</p>
                                    <p class="text-xs">${movement.previous_stock} → ${movement.new_stock}</p>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                container.innerHTML = html;
            })
            .catch(error => {
                console.error('Error loading movements:', error);
                container.innerHTML = '<p class="text-red-500 text-center py-8">Error loading movements</p>';
            });
        }

        function viewAllMovements() {
            const modal = document.getElementById('all-movements-modal');
            const container = document.getElementById('all-movements-content');

            container.innerHTML = '<div class="text-center py-8"><i class="bx bx-loader-alt bx-spin text-2xl text-gray-400"></i></div>';
            modal.classList.remove('hidden');

            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_stock_movements&shop_id=6&limit=100'
            })
            .then(response => response.json())
            .then(movements => {
                if (movements.length === 0) {
                    container.innerHTML = '<p class="text-gray-500 text-center py-8">No movements found</p>';
                    return;
                }

                let html = `
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock Change</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                `;

                movements.forEach(movement => {
                    const date = new Date(movement.created_at).toLocaleString();
                    html += `
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">${movement.product_name}</td>
                            <td class="px-4 py-3 text-sm">
                                <span class="movement-badge movement-${movement.movement_type} px-2 py-1 rounded-full text-xs font-medium">
                                    ${movement.movement_type.toUpperCase()}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm ${movement.movement_type === 'in' ? 'text-green-600' : 'text-red-600'} font-medium">
                                ${movement.movement_type === 'in' ? '+' : ''}${movement.quantity}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">${movement.previous_stock} → ${movement.new_stock}</td>
                            <td class="px-4 py-3 text-sm text-gray-600">${movement.reason || 'No reason'}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">${date}</td>
                        </tr>
                    `;
                });

                html += '</tbody></table></div>';
                container.innerHTML = html;
            })
            .catch(error => {
                console.error('Error loading all movements:', error);
                container.innerHTML = '<p class="text-red-500 text-center py-8">Error loading movements</p>';
            });
        }

        function closeAllMovementsModal() {
            document.getElementById('all-movements-modal').classList.add('hidden');
        }

        // Export Functions
        function exportStockReport() {
            // Create CSV content
            let csvContent = "Product ID,Product Name,Category,Current Stock,Stock Level,Cost Price,Selling Price,Total Value,Unit\n";

            stockData.forEach(item => {
                csvContent += `${item.product_id},"${item.name}","${item.category}",${item.current_stock},"${item.stock_level}",${item.cost_price || 0},${item.selling_price || 0},${item.total_value || 0},"${item.unit}"\n`;
            });

            // Create and download file
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement("a");
            const url = URL.createObjectURL(blob);
            link.setAttribute("href", url);
            link.setAttribute("download", `stock_report_${new Date().toISOString().split('T')[0]}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>

    <style>
        .movement-badge {
            font-size: 0.75rem;
        }

        .movement-badge.movement-in {
            background-color: #d1fae5;
            color: #065f46;
        }

        .movement-badge.movement-out {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .movement-badge.movement-adjustment {
            background-color: #dbeafe;
            color: #1e40af;
        }
    </style>
</body>
</html>
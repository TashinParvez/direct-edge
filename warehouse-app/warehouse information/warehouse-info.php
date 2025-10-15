<?php
// DB: connect and fetch metrics + inventory rows
require_once __DIR__ . '/../../include/connect-db.php';


                                include '../../include/navbar.php';
                                $admin_id = isset($user_id) ? $user_id : 65;

// Metrics
$totalCapacity = 0;
$usedCapacity = 0;
$itemCount = 0;

$resCap = mysqli_query($conn, "SELECT SUM(capacity_total) AS total_capacity, SUM(capacity_used) AS used_capacity FROM warehouses");
if ($resCap) {
    $capRow = mysqli_fetch_assoc($resCap);
    $totalCapacity = (int)($capRow['total_capacity'] ?? 0);
    $usedCapacity = (int)($capRow['used_capacity'] ?? 0);
}
$freeCapacity = $totalCapacity - $usedCapacity;
if ($freeCapacity < 0) {
    $freeCapacity = 0;
}

$resCount = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM warehouse_products");
if ($resCount) {
    $cntRow = mysqli_fetch_assoc($resCount);
    $itemCount = (int)($cntRow['cnt'] ?? 0);
}

// Inventory rows
$inventoryRows = [];
$sql = "SELECT
            wp.id,
            wp.product_id,
            p.name AS product_name,
            p.special_instructions AS product_instructions,
            p.unit AS product_unit,
            wp.quantity,
            wp.unit_volume,
            CASE WHEN wp.request_status = 1 THEN 'In progress' ELSE 'Completed' END AS status,
            w.warehouse_id,
            w.name AS warehouse_name,
            wp.agent_id,
            wp.offer_percentage,
            wp.offer_start,
            wp.offer_end,
            wp.inbound_stock_date,
            wp.expiry_date,
            wp.last_updated
        FROM warehouse_products wp
        JOIN products p ON p.product_id = wp.product_id
        JOIN warehouses w ON w.warehouse_id = wp.warehouse_id
        ORDER BY wp.last_updated DESC";
$resInv = mysqli_query($conn, $sql);
if ($resInv) {
    while ($row = mysqli_fetch_assoc($resInv)) {
        $inventoryRows[] = $row;
    }
}

$currentDate = new DateTime();

// Load warehouses for selects
$warehouses = [];
$resWh = mysqli_query($conn, "SELECT warehouse_id, name FROM warehouses ORDER BY name");
if ($resWh) {
    while ($w = mysqli_fetch_assoc($resWh)) {
        $warehouses[] = $w;
    }
}

// Load products for selects (product code implied as PRD-<id>)
$products = [];
$resPr = mysqli_query($conn, "SELECT product_id, name, unit, special_instructions FROM products ORDER BY name");
if ($resPr) {
    while ($p = mysqli_fetch_assoc($resPr)) {
        $products[] = $p;
    }
}

// Load agents (users with role = 'Agent') for selects
$agents = [];
$resAg = mysqli_query($conn, "SELECT user_id, full_name FROM users WHERE role = 'Agent' ORDER BY full_name");
if ($resAg) {
    while ($a = mysqli_fetch_assoc($resAg)) {
        $agents[] = $a;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warehouse Info</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    }

    .metric-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .metric-card:hover {
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

    .table-container {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    th,
    td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
    }

    th {
        background-color: #f9fafb;
        font-weight: 600;
        color: #1f2937;
    }

    tr:hover {
        background-color: #f3f4f6;
    }

    .low-stock {
        color: #dc2626;
        font-weight: 500;
    }

    .in-progress {
        color: #f59e0b;
    }

    .completed {
        color: #16a34a;
    }

    .dropdown-menu {
        max-height: 300px;
        overflow-y: auto;
    }

    @media (max-width: 640px) {
        .metrics-grid {
            grid-template-columns: 1fr;
        }

        .controls {
            flex-direction: column;
        }

        .modal-content {
            flex-direction: column;
        }
    }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <div class="container max-w-7xl mx-auto p-4 sm:p-6 lg:p-8">
        <div class="flex items-center justify-center mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Warehouse Products</h1>
        </div>

        <!-- Metrics Section -->
        <div class="metrics-grid grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <div class="metric-card bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h3 class="text-sm font-medium text-gray-500">Total Capacity</h3>
                <p class="text-2xl font-semibold text-gray-900"><?php echo $totalCapacity; ?> units</p>
            </div>
            <div class="metric-card bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h3 class="text-sm font-medium text-gray-500">Free Capacity</h3>
                <p class="text-2xl font-semibold text-gray-900"><?php echo $freeCapacity; ?> units</p>
            </div>
            <div class="metric-card bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h3 class="text-sm font-medium text-gray-500">Number of Items</h3>
                <p class="text-2xl font-semibold text-gray-900"><?php echo $itemCount; ?></p>
            </div>
        </div>

        <!-- Controls -->
        <div class="controls flex flex-col sm:flex-row gap-4 mb-8">
            <input type="text" id="searchInput" placeholder="Search Products..."
                class="flex-1 px-4 py-2 border border-gray-200 rounded-lg input-field focus:outline-none focus:ring-0"
                oninput="filterTable()">
            <div class="relative">
                <button
                    class="bg-white px-4 py-2 border border-gray-200 rounded-lg select-field focus:outline-none flex items-center"
                    onclick="toggleDropdown()">Filter by: All Products <i class='bx bx-chevron-down ml-2'></i></button>
                <div id="dropdownMenu"
                    class="dropdown-menu absolute hidden bg-white border border-gray-200 rounded-lg shadow-lg mt-2 w-64 z-10">
                    <div class="p-4">
                        <div class="group mb-4">
                            <span class="group-title text-sm font-medium text-gray-700">Status</span>
                            <label class="block"><input type="checkbox" class="filter-option" value="all" checked>
                                All</label>
                            <label class="block"><input type="checkbox" class="filter-option" value="In progress"> In
                                progress</label>
                            <label class="block"><input type="checkbox" class="filter-option" value="Completed">
                                Completed</label>
                        </div>
                        <div class="group mb-4">
                            <span class="group-title text-sm font-medium text-gray-700">Capacity</span>
                            <label class="block"><input type="checkbox" class="filter-option" value="all" checked>
                                All</label>
                            <label class="block"><input type="checkbox" class="filter-option" value="low"> Low</label>
                            <label class="block"><input type="checkbox" class="filter-option" value="medium">
                                Medium</label>
                            <label class="block"><input type="checkbox" class="filter-option" value="high"> High</label>
                        </div>
                        <div class="group mb-4">
                            <span class="group-title text-sm font-medium text-gray-700">Warehouse</span>
                            <label class="block"><input type="checkbox" class="filter-option" value="all" checked>
                                All</label>
                            <?php foreach ($warehouses as $w) { ?>
                            <label class="block"><input type="checkbox" class="filter-option"
                                    value="<?php echo htmlspecialchars($w['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($w['name'], ENT_QUOTES, 'UTF-8'); ?></label>
                            <?php } ?>
                        </div>
                        <div class="group mb-4">
                            <span class="group-title text-sm font-medium text-gray-700">Unit</span>
                            <label class="block"><input type="checkbox" class="filter-option" value="all" checked>
                                All</label>
                            <?php
                            $units = [];
                            foreach ($products as $p) {
                                $u = trim((string)($p['unit'] ?? ''));
                                if ($u !== '' && !in_array($u, $units, true)) $units[] = $u;
                            }
                            sort($units);
                            foreach ($units as $u) { ?>
                            <label class="block"><input type="checkbox" class="filter-option"
                                    value="<?php echo htmlspecialchars($u, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($u, ENT_QUOTES, 'UTF-8'); ?></label>
                            <?php } ?>
                        </div>
                        <div class="flex justify-between">
                            <button class="text-sm text-blue-600 hover:underline" onclick="selectAll()">Select
                                All</button>
                            <button class="bg-blue-600 text-white px-4 py-2 rounded-lg btn"
                                onclick="applyFilters()">Apply</button>
                        </div>
                    </div>
                </div>
            </div>
            <button id="addProductBtn" class="bg-blue-600 text-white px-4 py-2 rounded-lg btn">Add Product</button>
            <button id="createOfferBtn" class="bg-gray-600 text-white px-4 py-2 rounded-lg btn">Create Offer</button>
        </div>

        <!-- Alerts -->
        <div id="successMessage" class="bg-green-100 text-green-700 p-4 rounded-lg mb-4 hidden">
            <span id="successText"></span>
        </div>
        <div id="lowStockAlert" class="bg-red-100 text-red-700 p-4 rounded-lg mb-4 hidden">
            Low stock detected for some products!
        </div>

        <!-- Product Table -->
        <div class="table-container bg-white rounded-xl shadow-sm border border-gray-100">
            <table id="productTable">
                <thead>
                    <tr>
                        <th>Product Code</th>
                        <th>Product Name</th>
                        <th>Special Instructions</th>
                        <th>Quantity</th>
                        <th>Unit Volume</th>
                        <th>Status</th>
                        <th>Warehouse</th>
                        <th>Agent ID</th>
                        <th>Offer Suggestion</th>
                        <th>Inbound Stock Date</th>
                        <th>Expiry Date</th>
                        <th>Last Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="productTableBody">
                    <?php foreach ($inventoryRows as $row) {
                        $statusClass = strtolower($row['status']) === 'completed' ? 'completed' : 'in-progress';
                        $lowStock = ((int)$row['quantity']) < 10 ? 'low-stock' : '';
                        $productCode = 'PRD-' . str_pad((string)$row['product_id'], 3, '0', STR_PAD_LEFT);
                        $productName = htmlspecialchars($row['product_name'] ?? '', ENT_QUOTES, 'UTF-8');
                        $specialInstructions = htmlspecialchars($row['product_instructions'] ?? '—', ENT_QUOTES, 'UTF-8');
                        $quantity = (int)$row['quantity'];
                        $unitVolume = htmlspecialchars((string)$row['unit_volume'], ENT_QUOTES, 'UTF-8');
                        $statusText = htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8');
                        $warehouseName = htmlspecialchars($row['warehouse_name'] ?? '', ENT_QUOTES, 'UTF-8');
                        $agentId = $row['agent_id'] !== null ? htmlspecialchars((string)$row['agent_id'], ENT_QUOTES, 'UTF-8') : '—';
                        $offerPct = $row['offer_percentage'];
                        $offerStart = $row['offer_start'];
                        $offerEnd = $row['offer_end'];
                        $offerSuggestion = ($offerPct !== null && $offerPct > 0)
                            ? ($offerStart && $offerEnd
                                ? ($offerPct . '% (' . $offerStart . ' to ' . $offerEnd . ')')
                                : ($offerPct . '%'))
                            : 'No Offer';
                        $inboundDate = $row['inbound_stock_date'] ?? '—';
                        $expiryDate = $row['expiry_date'] ?? '—';
                        $lastUpdated = $row['last_updated'] ?? '—';
                        $productUnit = htmlspecialchars($row['product_unit'] ?? '', ENT_QUOTES, 'UTF-8');
                        echo "
                        <tr data-id='" . (int)$row['id'] . "' data-product-id='" . (int)$row['product_id'] . "' data-warehouse-id='" . (int)$row['warehouse_id'] . "' data-offer='" . htmlspecialchars($offerSuggestion, ENT_QUOTES, 'UTF-8') . "' data-unit='" . $productUnit . "'>
                            <td title='" . $productCode . "'>" . $productCode . "</td>
                            <td title='" . $productName . "'>" . $productName . "</td>
                            <td title='" . ($specialInstructions !== '' ? $specialInstructions : '—') . "'>" . ($specialInstructions !== '' ? $specialInstructions : '—') . "</td>
                            <td class='" . $lowStock . "' title='" . $quantity . "'>" . $quantity . "</td>
                            <td title='" . $unitVolume . "'>" . $unitVolume . "</td>
                            <td class='" . $statusClass . "' title='" . $statusText . "'>" . $statusText . "</td>
                            <td title='" . $warehouseName . "'>" . $warehouseName . "</td>
                            <td title='" . $agentId . "'>" . $agentId . "</td>
                            <td>
                                <button class='text-blue-600 hover:underline' onclick='showOfferSuggestion(" . (int)$row['id'] . ")'>" . htmlspecialchars($offerSuggestion, ENT_QUOTES, 'UTF-8') . "</button>
                            </td>
                            <td title='" . ($inboundDate ?: '—') . "'>" . ($inboundDate ?: '—') . "</td>
                            <td title='" . ($expiryDate ?: '—') . "'>" . ($expiryDate ?: '—') . "</td>
                            <td title='" . $lastUpdated . "'>" . $lastUpdated . "</td>
                            <td class='flex gap-2'>
                                <button class='text-blue-600 hover:text-blue-800' onclick='editProduct(" . (int)$row['id'] . ")'><i class='bx bx-edit'></i></button>
                                <button class='text-red-600 hover:text-red-800' onclick='deleteProduct(" . (int)$row['id'] . ")'><i class='bx bx-trash'></i></button>
                                <button class='text-gray-600 hover:text-gray-800' onclick='manageOffer(" . (int)$row['id'] . ")'><i class='bx bx-tag'></i></button>
                            </td>
                        </tr>";
                    } ?>
                </tbody>
                <tfoot id="totalRow">
                    <tr>
                        <td colspan="6">
                            <span class="font-medium">Total Items:</span>
                            <span id="totalItems" class="ml-2"><?php echo count($inventoryRows); ?></span>
                        </td>
                        <td colspan="7">
                            <span class="font-medium">Total Quantity:</span>
                            <span id="totalQuantity"
                                class="ml-2"><?php echo array_sum(array_column($inventoryRows, 'quantity')); ?></span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination flex items-center justify-between mt-4">
            <button id="prevBtn" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg btn disabled:opacity-50" disabled
                onclick="changePage(-1)">Previous</button>
            <div id="pageNumbers" class="text-sm text-gray-600"></div>
            <button id="nextBtn" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg btn disabled:opacity-50"
                onclick="changePage(1)">Next</button>
            <div class="flex items-center gap-2">
                <select id="rowsPerPage"
                    class="px-2 py-1 border border-gray-200 rounded-lg select-field focus:outline-none"
                    onchange="changeRowsPerPage()">
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="0">All</option>
                </select>
                <span class="text-sm text-gray-600">Rows per page</span>
            </div>
        </div>

        <!-- Add Product Modal -->
        <div id="addProductPopup"
            class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden z-50">
            <div class="bg-white rounded-xl max-w-2xl w-full p-6 modal-content modal">
                <button onclick="closePopup('add')"
                    class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl">&times;</button>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Add New Product</h2>
                <form id="addProductForm" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-600 mb-1">Product Code</label>
                        <select id="productCodeSelect" name="productCodeSelect"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg select-field focus:outline-none"
                            required>
                            <option value="">Select code</option>
                            <?php foreach ($products as $p) {
                                $code = 'PRD-' . str_pad((string)$p['product_id'], 3, '0', STR_PAD_LEFT); ?>
                            <option value="<?php echo (int)$p['product_id']; ?>"
                                data-instructions="<?php echo htmlspecialchars($p['special_instructions'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                data-unit="<?php echo htmlspecialchars($p['unit'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo $code; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 mb-1">Product Name</label>
                        <select id="productNameSelect" name="productNameSelect"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg select-field focus:outline-none"
                            required>
                            <option value="">Select product</option>
                            <?php foreach ($products as $p) { ?>
                            <option value="<?php echo (int)$p['product_id']; ?>"
                                data-instructions="<?php echo htmlspecialchars($p['special_instructions'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                data-unit="<?php echo htmlspecialchars($p['unit'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 mb-1">Special Instructions</label>
                        <input type="text" id="specialInstructions" name="specialInstructions"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg input-field focus:outline-none">
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 mb-1">Inbound Stock Date</label>
                        <input type="date" id="dateAdded" name="dateAdded"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg input-field focus:outline-none"
                            required>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 mb-1">Quantity</label>
                        <input type="number" id="quantity" name="quantity" min="1"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg input-field focus:outline-none"
                            required>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 mb-1">Unit Volume</label>
                        <input type="number" id="unitVolume" name="unitVolume" step="0.01" min="0"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg input-field focus:outline-none"
                            required>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 mb-1">Status</label>
                        <select id="status" name="status"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg select-field focus:outline-none"
                            required>
                            <option value="In progress">In progress</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 mb-1">Warehouse</label>
                        <select id="warehouse" name="warehouse"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg select-field focus:outline-none"
                            required>
                            <?php foreach ($warehouses as $w) { ?>
                            <option value="<?php echo (int)$w['warehouse_id']; ?>">
                                <?php echo htmlspecialchars($w['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 mb-1">Agent ID</label>
                        <select id="agentId" name="agentId"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg select-field focus:outline-none">
                            <option value="">Select agent</option>
                            <?php foreach ($agents as $a) { ?>
                            <option value="<?php echo (int)$a['user_id']; ?>">
                                <?php echo (int)$a['user_id'] . ' - ' . htmlspecialchars($a['full_name'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 mb-1">Expiry Date</label>
                        <input type="date" id="expiryDate" name="expiryDate"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg input-field focus:outline-none">
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg btn w-full">Add
                            Product</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Product Modal -->
        <div id="editProductPopup"
            class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden z-50">
            <div class="bg-white rounded-xl max-w-2xl w-full p-6 modal-content modal">
                <button onclick="closePopup('edit')"
                    class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl">&times;</button>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Edit Product</h2>
                <form id="editProductForm" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <input type="hidden" id="editProductId">
                    <input type="hidden" id="editProductProductId">
                    <div>
                        <label class="text-sm text-gray-600 mb-1">Product Code</label>
                        <input type="text" id="editProductCode" name="editProductCode"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg input-field focus:outline-none"
                            readonly>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 mb-1">Product Name</label>
                        <input type="text" id="editProductName" name="editProductName"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg input-field focus:outline-none"
                            readonly>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 mb-1">Special Instructions</label>
                        <input type="text" id="editSpecialInstructions" name="editSpecialInstructions"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg input-field focus:outline-none">
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 mb-1">Inbound Stock Date</label>
                        <input type="date" id="editDateAdded" name="editDateAdded"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg input-field focus:outline-none"
                            required>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 mb-1">Quantity</label>
                        <input type="number" id="editQuantity" name="editQuantity" min="1"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg input-field focus:outline-none"
                            required>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 mb-1">Unit Volume</label>
                        <input type="number" id="editUnitVolume" name="editUnitVolume" step="0.01" min="0"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg input-field focus:outline-none"
                            required>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 mb-1">Status</label>
                        <select id="editStatus" name="editStatus"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg select-field focus:outline-none"
                            required>
                            <option value="In progress">In progress</option>
                            <option value="Completed">Completed</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 mb-1">Warehouse</label>
                        <select id="editWarehouse" name="editWarehouse"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg select-field focus:outline-none"
                            required>
                            <?php foreach ($warehouses as $w) { ?>
                            <option value="<?php echo (int)$w['warehouse_id']; ?>">
                                <?php echo htmlspecialchars($w['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 mb-1">Agent ID</label>
                        <select id="editAgentId" name="editAgentId"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg select-field focus:outline-none">
                            <option value="">Select agent</option>
                            <?php foreach ($agents as $a) { ?>
                            <option value="<?php echo (int)$a['user_id']; ?>">
                                <?php echo (int)$a['user_id'] . ' - ' . htmlspecialchars($a['full_name'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 mb-1">Expiry Date</label>
                        <input type="date" id="editExpiryDate" name="editExpiryDate"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg input-field focus:outline-none">
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg btn w-full">Update
                            Product</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Offer Suggestion Modal -->
        <div id="offerSuggestionPopup"
            class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden z-50">
            <div class="bg-white rounded-xl max-w-lg w-full p-6 modal-content modal">
                <button onclick="closePopup('offer-suggestion')"
                    class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl">&times;</button>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Offer Suggestion</h2>
                <div id="offerSuggestionDetails" class="text-sm text-gray-600 mb-4"></div>
                <div class="flex gap-4">
                    <button id="offerSuggestionApplyBtn"
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg btn flex-1">Apply Suggestion</button>
                    <button id="offerSuggestionEditBtn"
                        class="bg-gray-600 text-white px-4 py-2 rounded-lg btn flex-1">Edit in Offer Form</button>
                </div>
            </div>
        </div>

        <!-- Offer Modal -->
        <div id="offerPopup" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden z-50">
            <div class="bg-white rounded-xl max-w-lg w-full p-6 modal-content modal">
                <button onclick="closePopup('offer')"
                    class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-xl">&times;</button>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Manage Offer</h2>
                <form id="offerForm" class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="text-sm text-gray-600 mb-1">Product</label>
                        <select id="offerProductId" name="offerProductId"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg select-field focus:outline-none"
                            required>
                            <?php foreach ($products as $p) { ?>
                            <option value="<?php echo (int)$p['product_id']; ?>">
                                <?php echo htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 mb-1">Discount (%)</label>
                        <input type="number" id="offerDiscount" name="offerDiscount" min="0" max="100"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg input-field focus:outline-none"
                            required>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 mb-1">Start Date</label>
                        <input type="date" id="offerStartDate" name="offerStartDate"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg input-field focus:outline-none"
                            required>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 mb-1">End Date</label>
                        <input type="date" id="offerEndDate" name="offerEndDate"
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg input-field focus:outline-none"
                            required>
                    </div>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg btn">Save Offer</button>
                </form>
            </div>
        </div>

        <!-- JavaScript -->
        <script>
        const inventoryData = <?php echo json_encode($inventoryRows); ?>;
        let currentPage = 1;
        let rowsPerPage = 10;
        let filteredData = [...inventoryData];

        function toggleDropdown() {
            document.getElementById('dropdownMenu').classList.toggle('hidden');
        }

        function selectAll() {
            document.querySelectorAll('.filter-option').forEach(checkbox => {
                checkbox.checked = true;
            });
            applyFilters();
        }

        function applyFilters() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const statusFilters = Array.from(document.querySelectorAll(
                '.filter-option[value="In progress"]:checked, .filter-option[value="Completed"]:checked')).map(cb =>
                cb.value);
            const capacityFilters = Array.from(document.querySelectorAll(
                '.filter-option[value="low"]:checked, .filter-option[value="medium"]:checked, .filter-option[value="high"]:checked'
            )).map(cb => cb.value);
            const warehouseFilters = Array.from(document.querySelectorAll(
                '.filter-option:not([value="all"]):not([value="In progress"]):not([value="Completed"]):not([value="low"]):not([value="medium"]):not([value="high"]):checked'
            )).map(cb => cb.value);
            const unitFilters = Array.from(document.querySelectorAll('.filter-option[data-unit]:checked')).map(cb => cb
                .value);

            filteredData = inventoryData.filter(row => {
                const matchesSearch = row.product_name.toLowerCase().includes(search);
                const matchesStatus = statusFilters.length === 0 || statusFilters.includes(row.status) ||
                    document.querySelector('.filter-option[value="all"]:checked');
                const matchesCapacity = capacityFilters.length === 0 || capacityFilters.includes(
                    getCapacityLevel(row.quantity)) || document.querySelector(
                    '.filter-option[value="all"]:checked');
                const matchesWarehouse = warehouseFilters.length === 0 || warehouseFilters.includes(row
                    .warehouse_name) || document.querySelector('.filter-option[value="all"]:checked');
                const matchesUnit = unitFilters.length === 0 || unitFilters.includes(row.product_unit) ||
                    document.querySelector('.filter-option[value="all"]:checked');
                return matchesSearch && matchesStatus && matchesCapacity && matchesWarehouse && matchesUnit;
            });

            updateTable();
            updatePagination();
            toggleLowStockAlert();
            toggleDropdown();
        }

        function getCapacityLevel(quantity) {
            if (quantity < 10) return 'low';
            if (quantity < 50) return 'medium';
            return 'high';
        }

        function filterTable() {
            applyFilters();
        }

        function updateTable() {
            const tbody = document.getElementById('productTableBody');
            tbody.innerHTML = '';
            const start = (currentPage - 1) * rowsPerPage;
            const end = rowsPerPage === 0 ? filteredData.length : start + rowsPerPage;
            const paginatedData = filteredData.slice(start, end);

            paginatedData.forEach(row => {
                const statusClass = row.status.toLowerCase() === 'completed' ? 'completed' : 'in-progress';
                const lowStock = row.quantity < 10 ? 'low-stock' : '';
                const productCode = `PRD-${String(row.product_id).padStart(3, '0')}`;
                const offerSuggestion = row.offer_percentage ?
                    `${row.offer_percentage}%${row.offer_start && row.offer_end ? ` (${row.offer_start} to ${row.offer_end})` : ''}` :
                    'No Offer';
                const tr = document.createElement('tr');
                tr.setAttribute('data-id', row.id);
                tr.setAttribute('data-product-id', row.product_id);
                tr.setAttribute('data-warehouse-id', row.warehouse_id);
                tr.setAttribute('data-offer', offerSuggestion);
                tr.setAttribute('data-unit', row.product_unit);
                tr.innerHTML = `
                        <td title="${productCode}">${productCode}</td>
                        <td title="${row.product_name}">${row.product_name}</td>
                        <td title="${row.product_instructions || '—'}">${row.product_instructions || '—'}</td>
                        <td class="${lowStock}" title="${row.quantity}">${row.quantity}</td>
                        <td title="${row.unit_volume}">${row.unit_volume}</td>
                        <td class="${statusClass}" title="${row.status}">${row.status}</td>
                        <td title="${row.warehouse_name}">${row.warehouse_name}</td>
                        <td title="${row.agent_id || '—'}">${row.agent_id || '—'}</td>
                        <td><button class="text-blue-600 hover:underline" onclick="showOfferSuggestion(${row.id})">${offerSuggestion}</button></td>
                        <td title="${row.inbound_stock_date || '—'}">${row.inbound_stock_date || '—'}</td>
                        <td title="${row.expiry_date || '—'}">${row.expiry_date || '—'}</td>
                        <td title="${row.last_updated || '—'}">${row.last_updated || '—'}</td>
                        <td class="flex gap-2">
                            <button class="text-blue-600 hover:text-blue-800" onclick="editProduct(${row.id})"><i class='bx bx-edit'></i></button>
                            <button class="text-red-600 hover:text-red-800" onclick="deleteProduct(${row.id})"><i class='bx bx-trash'></i></button>
                            <button class="text-gray-600 hover:text-gray-800" onclick="manageOffer(${row.id})"><i class='bx bx-tag'></i></button>
                        </td>`;
                tbody.appendChild(tr);
            });

            document.getElementById('totalItems').textContent = filteredData.length;
            document.getElementById('totalQuantity').textContent = filteredData.reduce((sum, row) => sum + parseInt(row
                .quantity), 0);
        }

        function toggleLowStockAlert() {
            const lowStock = filteredData.some(row => row.quantity < 10);
            document.getElementById('lowStockAlert').classList.toggle('hidden', !lowStock);
        }

        function changePage(delta) {
            currentPage += delta;
            updatePagination();
            updateTable();
        }

        function changeRowsPerPage() {
            rowsPerPage = parseInt(document.getElementById('rowsPerPage').value) || 0;
            currentPage = 1;
            updatePagination();
            updateTable();
        }

        function updatePagination() {
            const totalPages = rowsPerPage === 0 ? 1 : Math.ceil(filteredData.length / rowsPerPage);
            currentPage = Math.min(Math.max(1, currentPage), totalPages);
            const pageNumbers = document.getElementById('pageNumbers');
            pageNumbers.textContent = `Page ${currentPage} of ${totalPages}`;
            document.getElementById('prevBtn').disabled = currentPage === 1;
            document.getElementById('nextBtn').disabled = currentPage === totalPages;
        }

        function closePopup(type) {
            document.getElementById(`${type}ProductPopup`).classList.add('hidden');
            document.getElementById('offerSuggestionPopup').classList.add('hidden');
            document.getElementById('offerPopup').classList.add('hidden');
        }

        function editProduct(id) {
            const row = filteredData.find(r => r.id === id);
            if (!row) return;
            document.getElementById('editProductId').value = row.id;
            document.getElementById('editProductProductId').value = row.product_id;
            document.getElementById('editProductCode').value = `PRD-${String(row.product_id).padStart(3, '0')}`;
            document.getElementById('editProductName').value = row.product_name;
            document.getElementById('editSpecialInstructions').value = row.product_instructions || '';
            document.getElementById('editDateAdded').value = row.inbound_stock_date || '';
            document.getElementById('editQuantity').value = row.quantity;
            document.getElementById('editUnitVolume').value = row.unit_volume;
            document.getElementById('editStatus').value = row.status;
            document.getElementById('editWarehouse').value = row.warehouse_id;
            document.getElementById('editAgentId').value = row.agent_id || '';
            document.getElementById('editExpiryDate').value = row.expiry_date || '';
            document.getElementById('editProductPopup').classList.remove('hidden');
        }

        function deleteProduct(id) {
            if (!confirm('Are you sure you want to delete this product?')) return;
            const button = event.target;
            button.disabled = true;
            button.classList.add('loading');

            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=delete_product&id=${id}`
                })
                .then(res => {
                    if (!res.ok) {
                        return res.text().then(text => {
                            throw new Error(
                                `Server error: ${res.status}. Please try again or contact support.`);
                        });
                    }
                    return res.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            throw new Error(
                                'Invalid response from server. Please try again or contact support.');
                        }
                    });
                })
                .then(data => {
                    button.disabled = false;
                    button.classList.remove('loading');
                    if (data.status === 'success') {
                        filteredData = filteredData.filter(row => row.id !== id);
                        inventoryData.length = 0;
                        inventoryData.push(...filteredData);
                        updateTable();
                        updatePagination();
                        toggleLowStockAlert();
                        showSuccess('Product deleted successfully.');
                    } else {
                        alert(data.message || 'Failed to delete product.');
                    }
                })
                .catch(err => {
                    button.disabled = false;
                    button.classList.remove('loading');
                    alert(err.message);
                });
        }

        function showOfferSuggestion(id) {
            const row = filteredData.find(r => r.id === id);
            if (!row) return;
            const details = document.getElementById('offerSuggestionDetails');
            details.innerHTML =
                `
                    <p><strong>Product:</strong> ${row.product_name}</p>
                    <p><strong>Current Offer:</strong> ${row.offer_percentage ? `${row.offer_percentage}%${row.offer_start && row.offer_end ? ` (${row.offer_start} to ${row.offer_end})` : ''}` : 'No Offer'}</p>
                    <p><strong>Suggested Offer:</strong> ${row.quantity < 10 ? '20% discount for 7 days' : '10% discount for 14 days'}</p>`;
            document.getElementById('offerSuggestionPopup').setAttribute('data-id', id);
            document.getElementById('offerSuggestionPopup').classList.remove('hidden');
        }

        function manageOffer(id) {
            const row = filteredData.find(r => r.id === id);
            if (!row) return;
            document.getElementById('offerProductId').value = row.product_id;
            document.getElementById('offerDiscount').value = row.offer_percentage || '';
            document.getElementById('offerStartDate').value = row.offer_start || '';
            document.getElementById('offerEndDate').value = row.offer_end || '';
            document.getElementById('offerPopup').setAttribute('data-id', id);
            document.getElementById('offerPopup').classList.remove('hidden');
        }

        function showSuccess(message) {
            const success = document.getElementById('successMessage');
            document.getElementById('successText').textContent = message;
            success.classList.remove('hidden');
            setTimeout(() => success.classList.add('hidden'), 3000);
        }

        document.getElementById('addProductBtn').onclick = () => {
            document.getElementById('addProductPopup').classList.remove('hidden');
        };

        document.getElementById('createOfferBtn').onclick = () => {
            document.getElementById('offerPopup').classList.remove('hidden');
        };

        document.getElementById('addProductForm').onsubmit = function(e) {
            e.preventDefault();
            const button = this.querySelector('button[type="submit"]');
            button.disabled = true;
            button.classList.add('loading');
            button.textContent = 'Adding...';

            const formData = new FormData(this);
            formData.append('action', 'add_product');
            fetch('', {
                    method: 'POST',
                    body: new URLSearchParams(formData)
                })
                .then(res => {
                    if (!res.ok) {
                        return res.text().then(text => {
                            throw new Error(
                                `Server error: ${res.status}. Please try again or contact support.`);
                        });
                    }
                    return res.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            throw new Error(
                                'Invalid response from server. Please try again or contact support.'
                            );
                        }
                    });
                })
                .then(data => {
                    button.disabled = false;
                    button.classList.remove('loading');
                    button.textContent = 'Add Product';
                    if (data.status === 'success') {
                        closePopup('add');
                        showSuccess('Product added successfully.');
                        // Note: Backend should return new row data to append to inventoryData
                    } else {
                        alert(data.message || 'Failed to add product.');
                    }
                })
                .catch(err => {
                    button.disabled = false;
                    button.classList.remove('loading');
                    button.textContent = 'Add Product';
                    alert(err.message);
                });
        };

        document.getElementById('editProductForm').onsubmit = function(e) {
            e.preventDefault();
            const button = this.querySelector('button[type="submit"]');
            button.disabled = true;
            button.classList.add('loading');
            button.textContent = 'Updating...';

            const formData = new FormData(this);
            formData.append('action', 'edit_product');
            formData.append('id', document.getElementById('editProductId').value);
            fetch('', {
                    method: 'POST',
                    body: new URLSearchParams(formData)
                })
                .then(res => {
                    if (!res.ok) {
                        return res.text().then(text => {
                            throw new Error(
                                `Server error: ${res.status}. Please try again or contact support.`);
                        });
                    }
                    return res.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            throw new Error(
                                'Invalid response from server. Please try again or contact support.'
                            );
                        }
                    });
                })
                .then(data => {
                    button.disabled = false;
                    button.classList.remove('loading');
                    button.textContent = 'Update Product';
                    if (data.status === 'success') {
                        closePopup('edit');
                        showSuccess('Product updated successfully.');
                        // Note: Backend should return updated row data to update inventoryData
                    } else {
                        alert(data.message || 'Failed to update product.');
                    }
                })
                .catch(err => {
                    button.disabled = false;
                    button.classList.remove('loading');
                    button.textContent = 'Update Product';
                    alert(err.message);
                });
        };

        document.getElementById('offerForm').onsubmit = function(e) {
            e.preventDefault();
            const button = this.querySelector('button[type="submit"]');
            button.disabled = true;
            button.classList.add('loading');
            button.textContent = 'Saving...';

            const formData = new FormData(this);
            formData.append('action', 'save_offer');
            formData.append('id', document.getElementById('offerPopup').getAttribute('data-id'));
            fetch('', {
                    method: 'POST',
                    body: new URLSearchParams(formData)
                })
                .then(res => {
                    if (!res.ok) {
                        return res.text().then(text => {
                            throw new Error(
                                `Server error: ${res.status}. Please try again or contact support.`);
                        });
                    }
                    return res.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            throw new Error(
                                'Invalid response from server. Please try again or contact support.'
                            );
                        }
                    });
                })
                .then(data => {
                    button.disabled = false;
                    button.classList.remove('loading');
                    button.textContent = 'Save Offer';
                    if (data.status === 'success') {
                        closePopup('offer');
                        showSuccess('Offer saved successfully.');
                        // Note: Backend should return updated row data to update inventoryData
                    } else {
                        alert(data.message || 'Failed to save offer.');
                    }
                })
                .catch(err => {
                    button.disabled = false;
                    button.classList.remove('loading');
                    button.textContent = 'Save Offer';
                    alert(err.message);
                });
        };

        document.getElementById('offerSuggestionApplyBtn').onclick = function() {
            const id = parseInt(document.getElementById('offerSuggestionPopup').getAttribute('data-id'));
            const row = filteredData.find(r => r.id === id);
            if (!row) return;
            const discount = row.quantity < 10 ? 20 : 10;
            const today = new Date();
            const startDate = today.toISOString().split('T')[0];
            const endDate = new Date(today.setDate(today.getDate() + (row.quantity < 10 ? 7 : 14))).toISOString()
                .split('T')[0];

            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=save_offer&id=${id}&offerProductId=${row.product_id}&offerDiscount=${discount}&offerStartDate=${startDate}&offerEndDate=${endDate}`
                })
                .then(res => {
                    if (!res.ok) {
                        return res.text().then(text => {
                            throw new Error(
                                `Server error: ${res.status}. Please try again or contact support.`);
                        });
                    }
                    return res.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            throw new Error(
                                'Invalid response from server. Please try again or contact support.'
                            );
                        }
                    });
                })
                .then(data => {
                    if (data.status === 'success') {
                        closePopup('offer-suggestion');
                        showSuccess('Offer applied successfully.');
                        // Note: Backend should return updated row data to update inventoryData
                    } else {
                        alert(data.message || 'Failed to apply offer.');
                    }
                })
                .catch(err => {
                    alert(err.message);
                });
        };

        document.getElementById('offerSuggestionEditBtn').onclick = function() {
            const id = parseInt(document.getElementById('offerSuggestionPopup').getAttribute('data-id'));
            closePopup('offer-suggestion');
            manageOffer(id);
        };

        document.getElementById('productCodeSelect').onchange = function() {
            const productId = this.value;
            const productNameSelect = document.getElementById('productNameSelect');
            productNameSelect.value = productId;
            const option = this.options[this.selectedIndex];
            document.getElementById('specialInstructions').value = option.getAttribute('data-instructions') || '';
        };

        document.getElementById('productNameSelect').onchange = function() {
            const productId = this.value;
            const productCodeSelect = document.getElementById('productCodeSelect');
            productCodeSelect.value = productId;
            const option = this.options[this.selectedIndex];
            document.getElementById('specialInstructions').value = option.getAttribute('data-instructions') || '';
        };

        window.onclick = function(event) {
            const dropdown = document.getElementById('dropdownMenu');
            if (!event.target.closest('.dropdown-menu') && !event.target.closest('.dropdown-toggle')) {
                dropdown.classList.add('hidden');
            }
            const modals = ['addProductPopup', 'editProductPopup', 'offerSuggestionPopup', 'offerPopup'];
            modals.forEach(modalId => {
                if (event.target === document.getElementById(modalId)) {
                    closePopup(modalId.replace('Popup', ''));
                }
            });
        };

        // Initialize
        applyFilters();
        toggleLowStockAlert();
        updatePagination();
        </script>
    </div>
</body>

</html>
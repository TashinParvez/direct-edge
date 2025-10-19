<?php
ob_start(); // Start output buffering to handle session_start() in sidebar
?>
<link rel="stylesheet" href="../../Include/sidebar.css">
<?php include '../../Include/SidebarWarehouse.php'; ?>

<?php
// DB: connect and fetch metrics + inventory rows
require_once __DIR__ . '/../../include/connect-db.php';


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
            CASE WHEN wp.request_status = 1 THEN 'Completed' ELSE 'Pending' END AS status,
            w.warehouse_id,
            w.name AS warehouse_name,
            (w.capacity_total - w.capacity_used) AS warehouse_free_space,
            wp.agent_id,
            wp.offer_percentage,
            wp.offer_start,
            wp.offer_end,
            wp.inbound_stock_date,
            wp.expiry_date,
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
    <link rel="stylesheet" href="../warehouse information/warehouse-info.css">
</head>

<body>
    <div class="container">
        <h1>Warehouse Products</h1>
        <p style="margin-bottom: 32px; font-size: 1.2rem; color: #555; font-weight: 400;">
            Products Available in the Warehouse
        </p>


        <!-- Metrics Section -->
        <?php

        // DB: connect and fetch metrics + inventory rows
        require_once __DIR__ . '/../../include/connect-db.php';

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
                    CASE WHEN wp.request_status = 1 THEN 'Completed' ELSE 'Pending' END AS status,
                    w.warehouse_id,
                    w.name AS warehouse_name,
                    (w.capacity_total - w.capacity_used) AS warehouse_free_space,
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
        <div class="metrics-grid">
            <div class="metric-card">
                <h3>Total Capacity</h3>
                <p id="totalCapacity"><?php echo $totalCapacity; ?> units</p>
            </div>
            <div class="metric-card">
                <h3>Free Capacity</h3>
                <p id="freeCapacity"><?php echo $freeCapacity; ?> units</p>
            </div>
            <div class="metric-card">
                <h3>Number of Items</h3>
                <p id="itemCount"><?php echo $itemCount; ?></p>
            </div>
        </div>

        <div class="controls">
            <input type="text" id="searchInput" placeholder="Search Products..." />
            <div class="filter-container">
                <label for="filterDropdown">Filter by:</label>
                <div class="dropdown">
                    <button class="dropdown-toggle" onclick="toggleDropdown()">All Products ▾</button>
                    <div class="dropdown-menu" id="dropdownMenu">
                        <div class="group">
                            <span class="group-title">Status</span>
                            <label><input type="checkbox" class="filter-option" value="all" checked> all</label>
                            <label><input type="checkbox" class="filter-option" value="In progress"> In progress</label>
                            <label><input type="checkbox" class="filter-option" value="Completed"> Completed</label>
                        </div>
                        <div class="group">
                            <span class="group-title">Capacity</span>
                            <label><input type="checkbox" class="filter-option" value="all" checked> all</label>
                            <label><input type="checkbox" class="filter-option" value="low"> low</label>
                            <label><input type="checkbox" class="filter-option" value="medium"> medium</label>
                            <label><input type="checkbox" class="filter-option" value="high"> high</label>
                        </div>
                        <div class="group">
                            <span class="group-title">Warehouse</span>
                            <label><input type="checkbox" class="filter-option" value="all" checked> all</label>
                            <?php foreach ($warehouses as $w) { ?>
                                <label><input type="checkbox" class="filter-option"
                                        value="<?php echo htmlspecialchars($w['name'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($w['name'], ENT_QUOTES, 'UTF-8'); ?></label>
                            <?php } ?>
                        </div>
                        <div class="group">
                            <span class="group-title">Unit</span>
                            <label><input type="checkbox" class="filter-option" value="all" checked> all</label>
                            <?php
                            $units = [];
                            foreach ($products as $p) {
                                $u = trim((string)($p['unit'] ?? ''));
                                if ($u !== '' && !in_array($u, $units, true)) $units[] = $u;
                            }
                            sort($units);
                            foreach ($units as $u) { ?>
                                <label><input type="checkbox" class="filter-option"
                                        value="<?php echo htmlspecialchars($u, ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars($u, ENT_QUOTES, 'UTF-8'); ?></label>
                            <?php } ?>
                        </div>
                        <div class="dropdown-footer">
                            <button type="button" onclick="selectAll()">Select all</button>
                            <button class="apply-btn" onclick="applyFilters()">Apply</button>
                        </div>
                    </div>
                </div>
            </div>
            <button id="addProductBtn">Add Product</button>
            <button id="createOfferBtn">Create Offer</button>
        </div>

        <div id="successMessage" class="success-message" style="display: none;">
            <span id="successText"></span>
        </div>

        <div id="lowStockAlert" class="alert alert-warning" style="display: none;">
            Low stock detected for some products!
        </div>

        <div id="productContainer" class="list-view">
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
                        <th>Warehouse Free Space</th>
                        <th>Agent Id</th>
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
                        $warehouseFreeSpace = isset($row['warehouse_free_space']) ? number_format((float)$row['warehouse_free_space']) : '—';
                        echo "
                        <tr data-id='" . (int)$row['id'] . "' data-product-id='" . (int)$row['product_id'] . "' data-warehouse-id='" . (int)$row['warehouse_id'] . "' data-offer='" . htmlspecialchars($offerSuggestion, ENT_QUOTES, 'UTF-8') . "' data-unit='" . $productUnit . "'>
                            <td title='" . $productCode . "'>" . $productCode . "</td>
                            <td title='" . $productName . "'>" . $productName . "</td>
                            <td title='" . ($specialInstructions !== '' ? $specialInstructions : '—') . "'>" . ($specialInstructions !== '' ? $specialInstructions : '—') . "</td>
                            <td class='" . $lowStock . "' title='" . $quantity . "'>" . $quantity . "</td>
                            <td title='" . $unitVolume . "'>" . $unitVolume . "</td>
                            <td class='" . $statusClass . "' title='" . $statusText . "'>" . $statusText . "</td>
                            <td title='" . $warehouseName . "'>" . $warehouseName . "</td>
                            <td title='" . $warehouseFreeSpace . "'>" . $warehouseFreeSpace . "</td>
                            <td title='" . $agentId . "'>" . $agentId . "</td>
                            <td>
                                <button class='btn offer-suggestion-link btn-primary' title='View offer suggestion'>" . htmlspecialchars($offerSuggestion, ENT_QUOTES, 'UTF-8') . "</button>
                            </td>
                            <td title='" . ($inboundDate ?: '—') . "'>" . ($inboundDate ?: '—') . "</td>
                            <td title='" . ($expiryDate ?: '—') . "'>" . ($expiryDate ?: '—') . "</td>
                            <td title='" . $lastUpdated . "'>" . $lastUpdated . "</td>
                            <td>
                                <button class='action-btn edit-btn' onclick='editProduct(" . (int)$row['id'] . ")'>✎</button>
                                <button class='action-btn delete-btn' onclick='deleteProduct(" . (int)$row['id'] . ")'>🗑</button>
                                <button class='action-btn offer-btn' onclick='manageOffer(" . (int)$row['id'] . ")'>🏷️</button>
                            </td>
                        </tr>";
                    } ?>
                </tbody>
                <tfoot id="totalRow">
                    <tr>

                        <td colspan="7">
                            <span class="label">TotalItems:</span>
                            <span class="value" id="totalItems">0</span>

                        </td>
                        <td colspan="7"><span class="label">TotalQuantity:</span>
                            <span class="value" id="totalQuantity">0</span>
                        </td>

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
                <option value="0">All</option>
            </select>
            <span>Rows per page</span>
        </div>

        <div id="addProductPopup" class="popup">
            <div class="popup-content">
                <span class="close-btn" onclick="closePopup('add')">&times;</span>
                <h2>Add New Product</h2>
                <form id="addProductForm">
                    <label for="productCodeSelect">Product Code:</label>
                    <select id="productCodeSelect" name="productCodeSelect" required>
                        <option value="">Select code</option>
                        <?php foreach ($products as $p) {
                            $code = 'PRD-' . str_pad((string)$p['product_id'], 3, '0', STR_PAD_LEFT); ?>
                            <option value="<?php echo (int)$p['product_id']; ?>"
                                data-instructions="<?php echo htmlspecialchars($p['special_instructions'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                data-unit="<?php echo htmlspecialchars($p['unit'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo $code; ?></option>
                        <?php } ?>
                    </select>

                    <label for="productNameSelect">Product Name:</label>
                    <select id="productNameSelect" name="productNameSelect" required>
                        <option value="">Select product</option>
                        <?php foreach ($products as $p) { ?>
                            <option value="<?php echo (int)$p['product_id']; ?>"
                                data-instructions="<?php echo htmlspecialchars($p['special_instructions'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                data-unit="<?php echo htmlspecialchars($p['unit'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php } ?>
                    </select>

                    <label for="specialInstructions">Special Instructions:</label>
                    <input type="text" id="specialInstructions" name="specialInstructions">

                    <label for="dateAdded">Inbound Stock Date:</label>
                    <input type="date" id="dateAdded" name="dateAdded" required>

                    <label for="quantity">Quantity:</label>
                    <input type="number" id="quantity" name="quantity" min="1" required>

                    <label for="unitVolume">Unit Volume:</label>
                    <input type="number" id="unitVolume" name="unitVolume" step="0.01" min="0" required>

                    <label for="status">Status:</label>
                    <select id="status" name="status" required>
                        <option>Pending</option>
                        <option>Completed</option>
                    </select>

                    <label for="warehouse">Warehouse:</label>
                    <select id="warehouse" name="warehouse" required>
                        <?php foreach ($warehouses as $w) { ?>
                            <option value="<?php echo (int)$w['warehouse_id']; ?>">
                                <?php echo htmlspecialchars($w['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php } ?>
                    </select>

                    <label for="agentId">Agent Id:</label>
                    <select id="agentId" name="agentId">
                        <option value="">Select agent</option>
                        <?php foreach ($agents as $a) { ?>
                            <option value="<?php echo (int)$a['user_id']; ?>">
                                <?php echo (int)$a['user_id'] . ' - ' . htmlspecialchars($a['full_name'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php } ?>
                    </select>

                    <label for="expiryDate">Expiry Date:</label>
                    <input type="date" id="expiryDate" name="expiryDate">

                    <button type="submit">Add Product</button>
                </form>
            </div>
        </div>

        <div id="editProductPopup" class="popup">
            <div class="popup-content">
                <span class="close-btn" onclick="closePopup('edit')">&times;</span>
                <h2>Edit Product</h2>
                <form id="editProductForm">
                    <input type="hidden" id="editProductId">
                    <input type="hidden" id="editProductProductId">
                    <label for="editProductCode">Product Code:</label>
                    <input type="text" id="editProductCode" name="editProductCode" readonly>

                    <label for="editProductName">Product Name:</label>
                    <input type="text" id="editProductName" name="editProductName" readonly>

                    <label for="editSpecialInstructions">Special Instructions:</label>
                    <input type="text" id="editSpecialInstructions" name="editSpecialInstructions">

                    <label for="editDateAdded">Inbound Stock Date:</label>
                    <input type="date" id="editDateAdded" name="editDateAdded" required>

                    <label for="editQuantity">Quantity:</label>
                    <input type="number" id="editQuantity" name="editQuantity" min="1" required>

                    <label for="editUnitVolume">Unit Volume:</label>
                    <input type="number" id="editUnitVolume" name="editUnitVolume" step="0.01" min="0" required>

                    <label for="editStatus">Status:</label>
                    <select id="editStatus" name="editStatus" required>
                        <option>Pending</option>
                        <option>Completed</option>
                    </select>

                    <label for="editWarehouse">Warehouse:</label>
                    <select id="editWarehouse" name="editWarehouse" required>
                        <?php foreach ($warehouses as $w) { ?>
                            <option value="<?php echo (int)$w['warehouse_id']; ?>">
                                <?php echo htmlspecialchars($w['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php } ?>
                    </select>

                    <label for="editAgentId">Agent Id:</label>
                    <select id="editAgentId" name="editAgentId">
                        <option value="">Select agent</option>
                        <?php foreach ($agents as $a) { ?>
                            <option value="<?php echo (int)$a['user_id']; ?>">
                                <?php echo (int)$a['user_id'] . ' - ' . htmlspecialchars($a['full_name'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php } ?>
                    </select>

                    <label for="editExpiryDate">Expiry Date:</label>
                    <input type="date" id="editExpiryDate" name="editExpiryDate">

                    <button type="submit">Update Product</button>
                </form>
            </div>
        </div>

        <!-- Offer Suggestion Modal -->
        <div id="offerSuggestionPopup" class="popup">
            <div class="popup-content">
                <span class="close-btn" onclick="closePopup('offer-suggestion')">&times;</span>
                <h2>Offer Suggestion</h2>
                <div id="offerSuggestionDetails">
                    <!-- Filled by JavaScript with product info and suggested offer -->
                </div>
                <div class="actions" style="margin-top:12px; display:flex; gap:8px; flex-wrap:wrap;">
                    <button id="offerSuggestionApplyBtn">Apply Suggestion</button>
                    <button id="offerSuggestionEditBtn">Edit in Offer Form</button>
                </div>
            </div>
        </div>

        <div id="offerPopup" class="popup">
            <div class="popup-content">
                <span class="close-btn" onclick="closePopup('offer')">&times;</span>
                <h2>Manage Offer</h2>
                <form id="offerForm">
                    <label for="offerProductId">Product:</label>
                    <select id="offerProductId" name="offerProductId" required></select>
                    <label for="offerDiscount">Discount (%):</label>
                    <input type="number" id="offerDiscount" name="offerDiscount" min="0" max="100" required>

                    <label for="offerStartDate">Start Date:</label>
                    <input type="date" id="offerStartDate" name="offerStartDate" required>

                    <label for="offerEndDate">End Date:</label>
                    <input type="date" id="offerEndDate" name="offerEndDate" required>

                    <button type="submit">Save Offer</button>
                </form>
            </div>
        </div>
    </div>

    <script src="warehouse-info.js?v=<?php echo time(); ?>"></script>
</body>

</html>
<?php
ob_end_flush(); // End output buffering
?>
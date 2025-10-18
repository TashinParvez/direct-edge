<?php
// Start output buffering to prevent header issues
ob_start();

// Include the notification helper
require_once __DIR__ . '/../../include/notification_helpers.php';
?>
<link rel="stylesheet" href="../../Include/sidebar.css">
<?php include '../../Include/SidebarWarehouse.php'; ?>

<?php
// manage_warehouse.php
include '../../include/connect-db.php'; // database connection

// Get admin_id from session (started by sidebar)
$admin_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 65;

// Get warehouse id from URL
$warehouseId = $_GET['id'] ?? null;

if (!$warehouseId) {
    die("Warehouse ID not provided.");
}

//======================== Update product info if submitted from modal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['updateProduct'])) {
    $wpId = $_POST['wp_id'] ?? null;
    $productId = $_POST['product_id'] ?? null;
    $productName = $_POST['product_name'] ?? null;
    $quantity = $_POST['quantity'] ?? null;
    $unitVolume = $_POST['unit_volume'] ?? null;
    $expiryDate = $_POST['expiry_date'] ?? null;
    $newStatusFlag = isset($_POST['request_status']) ? (int)$_POST['request_status'] : 0;

    if ($wpId && $productId && $quantity !== null && $unitVolume !== null) {
        $conn->begin_transaction();
        try {
            // 1. Get current product state before update
            $currentProductStmt = $conn->prepare("SELECT quantity, unit_volume, request_status, agent_id FROM warehouse_products WHERE id = ? FOR UPDATE");
            $currentProductStmt->bind_param("i", $wpId);
            $currentProductStmt->execute();
            $currentProduct = $currentProductStmt->get_result()->fetch_assoc();
            $currentProductStmt->close();

            if (!$currentProduct) {
                throw new Exception("Product not found.");
            }

            $oldStatusFlag = (int)$currentProduct['request_status'];
            // Volume is only counted if status is 1 (Approved)
            $oldVolume = ($oldStatusFlag === 1) ? (float)$currentProduct['quantity'] * (float)$currentProduct['unit_volume'] : 0;
            $newVolume = ($newStatusFlag === 1) ? (float)$quantity * (float)$unitVolume : 0;
            $volumeDelta = $newVolume - $oldVolume;

            // 2. Update products table (name)
            $productUpdateStmt = $conn->prepare("UPDATE products SET name = ? WHERE product_id = ?");
            $productUpdateStmt->bind_param("si", $productName, $productId);
            $productUpdateStmt->execute();
            $productUpdateStmt->close();

            // 3. Update warehouse_products table
            $wpUpdateStmt = $conn->prepare("UPDATE warehouse_products SET quantity = ?, unit_volume = ?, expiry_date = ?, request_status = ? WHERE id = ?");
            $wpUpdateStmt->bind_param("idssi", $quantity, $unitVolume, $expiryDate, $newStatusFlag, $wpId);
            $wpUpdateStmt->execute();
            $wpUpdateStmt->close();

            // 4. Adjust warehouse capacity if volume changed
            if ($volumeDelta != 0) {
                $capacityUpdateStmt = $conn->prepare("UPDATE warehouses SET capacity_used = capacity_used + ? WHERE warehouse_id = ?");
                $capacityUpdateStmt->bind_param("di", $volumeDelta, $warehouseId);
                $capacityUpdateStmt->execute();
                $capacityUpdateStmt->close();
            }

            // 5. Commit the transaction
            $conn->commit();
            $success = "Product updated successfully!";

            // 6. Send notification if status changed
            if ($newStatusFlag !== $oldStatusFlag && !empty($currentProduct['agent_id'])) {
                $statusText = ($newStatusFlag === 1) ? 'Approved' : 'In Processing';
                $message = "The status of your product request for '{$productName}' has been updated to: {$statusText}.";
                $link = "/direct-edge/warehouse-app/warehouse%20information/warehouse-info.php?highlight_id={$wpId}";
                create_notification($conn, $currentProduct['agent_id'], 'request_approved', $message, $link);
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error updating product: " . $e->getMessage();
        }
    } else {
        $error = "Invalid product data provided.";
    }

    // Redirect to refresh the page after update
    ob_end_clean(); // Clear buffered output before redirect
    header("Location: manage_warehouse.php?id=" . $warehouseId . "&product_updated=1");
    exit;
}

//======================== Fetch warehouse info
$stmt = $conn->prepare("SELECT * FROM warehouses WHERE warehouse_id = ?");
$stmt->bind_param("i", $warehouseId);
$stmt->execute();
$warehouse = $stmt->get_result()->fetch_assoc();

//======================== Update warehouse info if submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['updateWarehouse'])) {
    $name = $_POST['name'] ?? $warehouse['name'];
    $location_city = $_POST['location_city'] ?? $warehouse['location'];
    $type = $_POST['type'] ?? $warehouse['type'];
    $status = $_POST['status'] ?? $warehouse['status'];
    $capacity_total = $_POST['capacity_total'] ?? $warehouse['capacity_total'];

    // IMPORTANT: capacity_used should not be directly editable. It should be calculated.
    // We will fetch it again to ensure it's current.
    $current_capacity_res = $conn->query("SELECT capacity_used FROM warehouses WHERE warehouse_id = $warehouseId");
    $current_capacity_used = $current_capacity_res->fetch_assoc()['capacity_used'] ?? 0;

    $location_full = $_POST['location_full'] ?? $warehouse['location'];

    // Assuming 'location' in DB is the full address, update with location_full
    $updateStmt = $conn->prepare("UPDATE warehouses SET name=?, location=?, type=?, status=?, capacity_total=? WHERE warehouse_id=?");
    $updateStmt->bind_param("ssssii", $name, $location_full, $type, $status, $capacity_total, $warehouseId);

    if ($updateStmt->execute()) {
        // Redirect to refresh the page and show success message
        ob_end_clean();
        header("Location: manage_warehouse.php?id=" . $warehouseId . "&updated=1");
        exit;
    } else {
        $error = "Error updating warehouse.";
    }
}

// Check for success message from redirect
if (isset($_GET['updated']) && $_GET['updated'] == '1') {
    $success = "Warehouse updated successfully!";
}

//======================== Fetch products of this warehouse (added product_id)
$productStmt = $conn->prepare("
    SELECT wp.id as wp_id, wp.product_id, p.name AS product_name, wp.quantity, wp.expiry_date, wp.unit_volume, p.img_url, wp.request_status
    FROM warehouse_products wp
    JOIN products p ON wp.product_id = p.product_id
    WHERE wp.warehouse_id = ?
");
$productStmt->bind_param("i", $warehouseId);
$productStmt->execute();
$result = $productStmt->get_result();

// Store all products in an array
$productsArray = [];
while ($row = $result->fetch_assoc()) {
    $productsArray[] = $row;
}

// Flush output buffer and send all content
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Warehouse - Stock Integrated</title>
    <link rel="icon" type="image/x-icon" href="../Logo/LogoBG.png">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .warehouse-card {
            transition: all 0.3s ease;
        }

        .warehouse-card:hover {
            background-color: #f9fafb;
        }

        .product-row {
            transition: all 0.2s ease;
        }

        .product-row:hover {
            background-color: #f3f4f6;
            transform: translateY(-1px);
        }

        .edit-btn {
            transition: all 0.2s ease;
        }

        .edit-btn:hover {
            transform: scale(1.1);
        }

        .modal {
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .modal-content {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }

        .alert-message {
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-fade-out {
            animation: fadeOut 0.5s ease-out forwards;
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
            }

            to {
                opacity: 0;
                transform: translateY(-10px);
            }
        }
    </style>
</head>

<body class="bg-gray-100">

    <section class="home-section p-0">
        <div class="flex justify-between items-center p-4">
            <h1 class="text-2xl font-bold">Manage Warehouse</h1>
        </div>

        <div class="container mx-auto px-4">

            <!-- Warehouse Info Card -->
            <div class="bg-white shadow-lg rounded-lg p-6 mb-6 warehouse-card relative">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-blue-700 mb-2">
                            <?= htmlspecialchars($warehouse['name']) ?>
                        </h2>
                        <p class="text-gray-600">Warehouse Management</p>
                    </div>

                    <!-- Edit Toggle Button -->
                    <div class="flex space-x-2">
                        <button id="warehouseEditIcon" onclick="enableWarehouseEdit()"
                            class="bg-blue-500 text-white p-2 rounded-lg hover:bg-blue-600 transition-colors no-print"
                            title="Edit Warehouse">
                            <i class='bx bx-edit text-lg'></i>
                        </button>
                        <button id="warehouseCancelIcon" onclick="disableWarehouseEdit()"
                            class="bg-gray-500 text-white p-2 rounded-lg hover:bg-gray-600 transition-colors no-print"
                            title="Cancel Edit" style="display: none;">
                            <i class='bx bx-x text-lg'></i>
                        </button>
                    </div>
                </div>

                <?php if (!empty($success)): ?>
                    <div id="successAlert" class="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded-lg alert-message">
                        <i class='bx bx-check-circle mr-2'></i><?= $success ?>
                    </div>
                <?php elseif (!empty($error)): ?>
                    <div id="errorAlert" class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg alert-message">
                        <i class='bx bx-error-circle mr-2'></i><?= $error ?>
                    </div>
                <?php endif; ?>

                <!-- Warehouse Info Form -->
                <form id="warehouseForm" method="POST" class="space-y-6">

                    <!-- Basic Info -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class='bx bx-building mr-1'></i>Warehouse Name
                            </label>
                            <input type="text" name="name" value="<?= htmlspecialchars($warehouse['name']) ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                                readonly>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class='bx bx-map mr-1'></i>Location (City)
                            </label>
                            <input type="text" name="location_city"
                                value="<?= htmlspecialchars($warehouse['location']) ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                                readonly>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class='bx bx-category mr-1'></i>Type
                            </label>
                            <select name="type"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                                disabled>
                                <option value="Normal" <?= ($warehouse['type'] == 'Normal') ? 'selected' : '' ?>>Normal
                                </option>
                                <option value="Cold Storage"
                                    <?= ($warehouse['type'] == 'Cold Storage') ? 'selected' : '' ?>>Cold Storage
                                </option>
                                <option value="Hazardous" <?= ($warehouse['type'] == 'Hazardous') ? 'selected' : '' ?>>
                                    Hazardous</option>
                            </select>
                        </div>
                    </div>

                    <!-- Capacity Info -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class='bx bx-check-circle mr-1'></i>Status
                            </label>
                            <select name="status"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                                disabled>
                                <option value="Active" <?= ($warehouse['status'] == 'Active') ? 'selected' : '' ?>>
                                    Active</option>
                                <option value="Inactive" <?= ($warehouse['status'] == 'Inactive') ? 'selected' : '' ?>>
                                    Inactive</option>
                                <option value="Under Maintenance"
                                    <?= ($warehouse['status'] == 'Under Maintenance') ? 'selected' : '' ?>>Under
                                    Maintenance</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class='bx bx-cube mr-1'></i>Total Capacity (m³)
                            </label>
                            <input type="number" name="capacity_total"
                                value="<?= htmlspecialchars($warehouse['capacity_total']) ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                                readonly>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class='bx bx-package mr-1'></i>Used Capacity (m³)
                            </label>
                            <input type="number" name="capacity_used"
                                value="<?= htmlspecialchars($warehouse['capacity_used']) ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-200"
                                readonly>
                            <small class="text-gray-500">This field is calculated automatically.</small>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class='bx bx-layer-plus mr-1'></i>Free Capacity (m³)
                            </label>
                            <input type="number" name="capacity_free"
                                value="<?= htmlspecialchars($warehouse['capacity_total'] - $warehouse['capacity_used']) ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50" readonly>
                        </div>
                    </div>

                    <!-- Full Address -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class='bx bx-location-plus mr-1'></i>Location (Full Address)
                        </label>
                        <textarea name="location_full"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                            rows="2" readonly><?= htmlspecialchars($warehouse['location']) ?></textarea>
                    </div>

                    <!-- Submit Button -->
                    <div id="updateWarehouseBtnDiv" style="display: none;" class="no-print">
                        <button type="submit" name="updateWarehouse"
                            class="bg-blue-500 text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-colors">
                            <i class='bx bx-save mr-2'></i>Update Warehouse
                        </button>
                    </div>
                </form>
            </div>

            <!-- Products Table Card -->
            <div class="bg-white shadow-lg rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-6 flex items-center">
                    <i class='bx bx-package mr-2 text-green-600'></i>Products in this Warehouse
                </h2>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Image</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Product</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Quantity</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Volume (m³)</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Expiry Date</th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider no-print">
                                    Edit</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($productsArray as $row): ?>
                                <?php
                                $qty = (float)$row['quantity'];
                                $unitVol = (float)$row['unit_volume'];
                                $totalVol = $qty * $unitVol; // m³
                                ?>
                                <tr class="product-row">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="h-16 w-16 rounded-lg overflow-hidden">
                                            <?php if ($row['img_url']): ?>
                                                <img src="<?= '../../' . htmlspecialchars($row['img_url']) ?>"
                                                    alt="<?= htmlspecialchars($row['product_name']) ?>"
                                                    class="h-16 w-16 object-cover">
                                            <?php else: ?>
                                                <div class="h-16 w-16 bg-gray-100 flex items-center justify-center rounded-lg">
                                                    <i class='bx bx-package text-2xl text-gray-400'></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($row['product_name']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?= htmlspecialchars($row['quantity']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?= number_format($totalVol, 2) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?= htmlspecialchars($row['expiry_date']) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($row['request_status'] == 1): ?>
                                            <span
                                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                <i class='bx bx-check mr-1'></i>Approved
                                            </span>
                                        <?php else: ?>
                                            <span
                                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                <i class='bx bx-time mr-1'></i>Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center no-print">
                                        <button onclick="openModal('modal-<?= $row['wp_id'] ?>')"
                                            class="text-blue-600 hover:text-blue-900 edit-btn">
                                            <i class='bx bx-edit text-lg'></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- Product Edit Modals -->
    <?php foreach ($productsArray as $row): ?>
        <?php
        $qty = (float)$row['quantity'];
        $unitVol = (float)$row['unit_volume'];
        $totalVol = $qty * $unitVol;
        ?>
        <div id="modal-<?= $row['wp_id'] ?>"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 modal">
            <div class="bg-white w-11/12 md:w-2/3 lg:w-1/2 rounded-lg shadow-lg overflow-hidden modal-content">
                <div class="flex">
                    <!-- Left: Product Image -->
                    <div class="w-1/3 bg-gray-100 p-4 flex items-center justify-center">
                        <?php if ($row['img_url']): ?>
                            <img src="<?= '../../' . htmlspecialchars($row['img_url']) ?>"
                                alt="<?= htmlspecialchars($row['product_name']) ?>" class="h-32 w-32 object-cover rounded">
                        <?php else: ?>
                            <div class="h-32 w-32 bg-gray-200 flex items-center justify-center rounded-lg">
                                <i class='bx bx-package text-4xl text-gray-400'></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Right: Editable Form -->
                    <div class="w-2/3 p-4">
                        <h2 class="text-lg font-semibold mb-4">Edit Product</h2>
                        <form method="POST" action="">
                            <input type="hidden" name="wp_id" value="<?= $row['wp_id'] ?>">
                            <input type="hidden" name="product_id" value="<?= $row['product_id'] ?>">

                            <div class="mb-2">
                                <label class="block text-sm font-medium text-gray-700">Product</label>
                                <input type="text" name="product_name" value="<?= htmlspecialchars($row['product_name']) ?>"
                                    class="w-full border rounded p-2 mt-1 focus:ring focus:ring-blue-200">
                            </div>

                            <div class="mb-2">
                                <label class="block text-sm font-medium text-gray-700">Quantity</label>
                                <input type="number" id="qty-<?= $row['wp_id'] ?>" name="quantity"
                                    value="<?= htmlspecialchars($row['quantity']) ?>"
                                    class="w-full border rounded p-2 mt-1 focus:ring focus:ring-blue-200">
                            </div>

                            <div class="mb-2">
                                <label class="block text-sm font-medium text-gray-700">Unit Volume</label>
                                <div class="flex items-center">
                                    <input type="number" step="0.01" id="unit-<?= $row['wp_id'] ?>" name="unit_volume"
                                        value="<?= htmlspecialchars($row['unit_volume']) ?>"
                                        class="w-full border rounded p-2 mt-1 focus:ring focus:ring-blue-200">
                                    <span class="unit-after">m³</span>
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="block text-sm font-medium text-gray-700">Total Volume</label>
                                <div class="flex items-center">
                                    <input type="number" step="0.01" id="total-<?= $row['wp_id'] ?>"
                                        value="<?= number_format($totalVol, 2, '.', '') ?>"
                                        class="w-full border rounded p-2 mt-1 bg-gray-100" readonly>
                                    <span class="unit-after">m³</span>
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="block text-sm font-medium text-gray-700">Expiry Date</label>
                                <input type="date" name="expiry_date" value="<?= htmlspecialchars($row['expiry_date']) ?>"
                                    class="w-full border rounded p-2 mt-1 focus:ring focus:ring-blue-200">
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Status</label>
                                <select name="request_status"
                                    class="w-full border rounded p-2 mt-1 focus:ring focus:ring-blue-200">
                                    <option value="1" <?= ($row['request_status'] == 1) ? 'selected' : '' ?>>Approved
                                    </option>
                                    <option value="0" <?= ($row['request_status'] == 0) ? 'selected' : '' ?>>Pending
                                    </option>
                                </select>
                            </div>
                            <div class="flex justify-end space-x-2">
                                <button type="button" onclick="closeModal('modal-<?= $row['wp_id'] ?>')"
                                    class="px-4 py-2 rounded border">Cancel</button>
                                <button type="submit" name="updateProduct"
                                    class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script>
        // Modal handlers for products
        function openModal(id) {
            document.getElementById(id).classList.remove('hidden');
        }

        function closeModal(id) {
            document.getElementById(id).classList.add('hidden');
        }

        // Recalculate total volume per modal when qty/unit changes
        <?php foreach ($productsArray as $row): ?>
                (function() {
                    const qtyEl = document.getElementById('qty-<?= $row['wp_id'] ?>');
                    const unitEl = document.getElementById('unit-<?= $row['wp_id'] ?>');
                    const totalEl = document.getElementById('total-<?= $row['wp_id'] ?>');

                    function recalc() {
                        const q = parseFloat(qtyEl.value) || 0;
                        const u = parseFloat(unitEl.value) || 0;
                        const t = q * u;
                        totalEl.value = t.toFixed(2);
                    }
                    qtyEl.addEventListener('input', recalc);
                    unitEl.addEventListener('input', recalc);
                })();
        <?php endforeach; ?>

        // Warehouse Info edit handlers
        function enableWarehouseEdit() {
            let form = document.getElementById('warehouseForm');
            Array.from(form.elements).forEach(el => {
                if (el.name !== "") {
                    if (el.type === 'select-one') el.disabled = false;
                    else el.readOnly = false;
                }
            });
            // Show button
            document.getElementById('updateWarehouseBtnDiv').style.display = '';
            // Hide edit icon, show cancel icon
            document.getElementById('warehouseEditIcon').style.display = 'none';
            document.getElementById('warehouseCancelIcon').style.display = '';
        }

        function disableWarehouseEdit() {
            let form = document.getElementById('warehouseForm');
            Array.from(form.elements).forEach(el => {
                if (el.name !== "") {
                    if (el.type === 'select-one') el.disabled = true;
                    else el.readOnly = true;
                }
            });
            // Hide button
            document.getElementById('updateWarehouseBtnDiv').style.display = 'none';
            // Show edit icon, hide cancel icon
            document.getElementById('warehouseEditIcon').style.display = '';
            document.getElementById('warehouseCancelIcon').style.display = 'none';
        }

        // Ensure selects stay enabled on submit so values post
        document.getElementById('warehouseForm').addEventListener('submit', function() {
            this.querySelectorAll('select').forEach(s => s.disabled = false);
        });

        // Auto-fade alert messages after 3 seconds
        window.addEventListener('DOMContentLoaded', function() {
            const successAlert = document.getElementById('successAlert');
            const errorAlert = document.getElementById('errorAlert');

            if (successAlert) {
                setTimeout(function() {
                    successAlert.classList.add('alert-fade-out');
                    setTimeout(function() {
                        successAlert.remove();
                    }, 500);
                }, 3000);
            }

            if (errorAlert) {
                setTimeout(function() {
                    errorAlert.classList.add('alert-fade-out');
                    setTimeout(function() {
                        errorAlert.remove();
                    }, 500);
                }, 3000);
            }
        });
    </script>
</body>

</html>
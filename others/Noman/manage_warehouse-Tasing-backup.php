<?php
// manage_warehouse.php
include '../../include/connect-db.php'; // database connection

// Get warehouse id from URL
$warehouseId = $_GET['id'] ?? 15;

if (!$warehouseId) {
    die("Warehouse ID not provided.");
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
    $capacity_used = $_POST['capacity_used'] ?? $warehouse['capacity_used'];
    $location_full = $_POST['location_full'] ?? $warehouse['location'];

    // Assuming 'location' in DB is the full address, update with location_full
    // If DB has separate fields, adjust accordingly
    $updateStmt = $conn->prepare("UPDATE warehouses SET name=?, location=?, type=?, status=?, capacity_total=?, capacity_used=? WHERE warehouse_id=?");
    $updateStmt->bind_param("ssssiii", $name, $location_full, $type, $status, $capacity_total, $capacity_used, $warehouseId);

    if ($updateStmt->execute()) {
        $success = "Warehouse updated successfully!";
        // refresh info
        $stmt->execute();
        $warehouse = $stmt->get_result()->fetch_assoc();
    } else {
        $error = "Error updating warehouse.";
    }
}

//======================== Fetch products of this warehouse
$productStmt = $conn->prepare("
    SELECT wp.id as id, p.name AS product_name, wp.quantity, wp.expiry_date, wp.unit_volume, wp.image_url, wp.request_status
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Warehouse</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .edit-warehouse-btn {
            transition: background 0.2s;
        }

        .edit-warehouse-btn:hover {
            background: #e0e7ff;
        }

        .warehouse-edit-icon {
            cursor: pointer;
        }
    </style>
</head>

<body class="bg-gray-50 p-8">

    <div class="max-w-5xl mx-auto bg-white shadow rounded-lg p-6 relative">
        <h1 class="text-3xl font-bold text-blue-700 mb-6">
            Manage Warehouse: <?= htmlspecialchars($warehouse['name']) ?>
        </h1>

        <!-- Top right warehouse info edit icon -->
        <span class="absolute top-8 right-8 warehouse-edit-icon" id="warehouseEditIcon" onclick="enableWarehouseEdit()" title="Edit Warehouse">
            <img width="28" height="28" src="https://img.icons8.com/material-rounded/28/create-new.png" alt="Edit warehouse" />
        </span>
        <!-- Top right warehouse info cancel icon (initially hidden) -->
        <span class="absolute top-8 right-8 warehouse-edit-icon" id="warehouseCancelIcon" onclick="disableWarehouseEdit()" title="Cancel Edit" style="display: none;">
            <img width="28" height="28" src="https://img.icons8.com/material-rounded/28/cancel.png" alt="Cancel edit" />
        </span>

        <?php if (!empty($success)): ?>
            <p class="mb-4 text-green-600 font-semibold"><?= $success ?></p>
        <?php elseif (!empty($error)): ?>
            <p class="mb-4 text-red-600 font-semibold"><?= $error ?></p>
        <?php endif; ?>

        <!-- ====================  Warehouse Info Update  ==================== -->
        <form id="warehouseForm" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">

            <!-- 1st Row -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Warehouse Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($warehouse['name']) ?>"
                    class="w-full border rounded p-2 mt-1 focus:ring focus:ring-blue-200" readonly>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Location (City)</label>
                <input type="text" name="location_city" value="<?= htmlspecialchars($warehouse['location']) ?>"
                    class="w-full border rounded p-2 mt-1 focus:ring focus:ring-blue-200" readonly>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Type</label>
                <select name="type" class="w-full border rounded p-2 mt-1 focus:ring focus:ring-blue-200" disabled>
                    <option value="Normal" <?= ($warehouse['type'] == 'Normal') ? 'selected' : '' ?>>Normal</option>
                    <option value="Cold Storage" <?= ($warehouse['type'] == 'Cold Storage') ? 'selected' : '' ?>>Cold Storage</option>
                    <option value="Hazardous" <?= ($warehouse['type'] == 'Hazardous') ? 'selected' : '' ?>>Hazardous</option>
                </select>
            </div>

            <!-- 2nd Row: 4-column grid -->
            <div class="mb-4 col-span-full grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" class="w-full border rounded p-2 mt-1 focus:ring focus:ring-blue-200" disabled>
                        <option value="Active" <?= ($warehouse['status'] == 'Active') ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= ($warehouse['status'] == 'Inactive') ? 'selected' : '' ?>>Inactive</option>
                        <option value="Under Maintenance" <?= ($warehouse['status'] == 'Under Maintenance') ? 'selected' : '' ?>>Under Maintenance</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Total Capacity</label>
                    <input type="number" name="capacity_total" value="<?= htmlspecialchars($warehouse['capacity_total']) ?>"
                        class="w-full border rounded p-2 mt-1 focus:ring focus:ring-blue-200" readonly>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Filled Capacity</label>
                    <input type="number" name="capacity_used" value="<?= htmlspecialchars($warehouse['capacity_used']) ?>"
                        class="w-full border rounded p-2 mt-1 focus:ring focus:ring-blue-200" readonly>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Free Capacity</label>
                    <input type="number" name="capacity_free" value="<?= htmlspecialchars($warehouse['capacity_total'] - $warehouse['capacity_used']) ?>"
                        class="w-full border rounded p-2 mt-1 focus:ring focus:ring-blue-200" readonly>
                </div>
            </div>

            <!-- 3rd Row: Full Address -->
            <div class="mb-4 col-span-full">
                <label class="block text-sm font-medium text-gray-700">Location (Full Address)</label>
                <textarea name="location_full" class="w-full border rounded p-2 mt-1 focus:ring focus:ring-blue-200" rows="2" readonly><?= htmlspecialchars($warehouse['location']) ?></textarea>
            </div>

            <!-- Submit Button (initially hidden) -->
            <div class="col-span-full" id="updateWarehouseBtnDiv" style="display: none;">
                <button type="submit" name="updateWarehouse"
                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Update Warehouse</button>
            </div>

        </form>

        <!-- =====================  Products Table  ====================== -->
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Products in this Warehouse</h2>
        <div class="overflow-x-auto">
            <table class="w-full border border-gray-200 rounded-lg overflow-hidden">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="p-3 text-left">Image</th>
                        <th class="p-3 text-left">Product</th>
                        <th class="p-3 text-left">Quantity</th>
                        <th class="p-3 text-left">Unit Volume</th>
                        <th class="p-3 text-left">Expiry Date</th>
                        <th class="p-3 text-left">Status</th>
                        <th class="p-3 text-left">Edit</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($productsArray as $row): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="p-3">
                                <?php if ($row['image_url']): ?>
                                    <img src="<?= '../' . htmlspecialchars($row['image_url']) ?>" alt="<?= htmlspecialchars($row['product_name']) ?>" class="h-16 w-16 object-cover rounded">
                                <?php else: ?>
                                    <span class="text-gray-400 italic">No Image</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 font-medium"><?= htmlspecialchars($row['product_name']) ?></td>
                            <td class="p-3"><?= htmlspecialchars($row['quantity']) ?></td>
                            <td class="p-3"><?= htmlspecialchars($row['unit_volume']) ?></td>
                            <td class="p-3"><?= htmlspecialchars($row['expiry_date']) ?></td>
                            <td class="p-3">
                                <?php if ($row['request_status'] == 1): ?>
                                    <span class="text-green-600 font-semibold">Approved</span>
                                <?php else: ?>
                                    <span class="text-yellow-600 font-semibold">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3">
                                <button onclick="openModal('modal-<?= $row['id'] ?>')" class="text-blue-500 hover:text-blue-700">
                                    <img width="24" height="24" src="https://img.icons8.com/material-rounded/24/create-new.png" alt="create-new" />
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- ============================ update modal ===================================== -->
            <?php foreach ($productsArray as $row): ?>
                <div id="modal-<?= $row['id'] ?>" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
                    <div class="bg-white w-11/12 md:w-2/3 lg:w-1/2 rounded-lg shadow-lg overflow-hidden">
                        <div class="flex">
                            <!-- Left: Product Image -->
                            <div class="w-1/3 bg-gray-100 p-4 flex items-center justify-center">
                                <?php if ($row['image_url']): ?>
                                    <img src="<?= '../' . htmlspecialchars($row['image_url']) ?>" alt="<?= htmlspecialchars($row['product_name']) ?>" class="h-32 w-32 object-cover rounded">
                                <?php else: ?>
                                    <span class="text-gray-400 italic">No Image</span>
                                <?php endif; ?>
                            </div>

                            <!-- Right: Editable Form -->
                            <div class="w-2/3 p-4">
                                <h2 class="text-lg font-semibold mb-4">Edit Product</h2>
                                <form method="POST" action="update_product.php">
                                    <input type="hidden" name="product_id" value="<?= $row['id'] ?>">

                                    <div class="mb-2">
                                        <label class="block text-sm font-medium text-gray-700">Product</label>
                                        <input type="text" name="product_name" value="<?= htmlspecialchars($row['product_name']) ?>" class="w-full border rounded p-2 mt-1 focus:ring focus:ring-blue-200">
                                    </div>

                                    <div class="mb-2">
                                        <label class="block text-sm font-medium text-gray-700">Quantity</label>
                                        <input type="number" name="quantity" value="<?= htmlspecialchars($row['quantity']) ?>" class="w-full border rounded p-2 mt-1 focus:ring focus:ring-blue-200">
                                    </div>

                                    <div class="mb-2">
                                        <label class="block text-sm font-medium text-gray-700">Unit Volume</label>
                                        <input type="text" name="unit_volume" value="<?= htmlspecialchars($row['unit_volume']) ?>" class="w-full border rounded p-2 mt-1 focus:ring focus:ring-blue-200">
                                    </div>

                                    <div class="mb-2">
                                        <label class="block text-sm font-medium text-gray-700">Expiry Date</label>
                                        <input type="date" name="expiry_date" value="<?= htmlspecialchars($row['expiry_date']) ?>" class="w-full border rounded p-2 mt-1 focus:ring focus:ring-blue-200">
                                    </div>

                                    <div class="mb-4">
                                        <label class="block text-sm font-medium text-gray-700">Status</label>
                                        <select name="request_status" class="w-full border rounded p-2 mt-1 focus:ring focus:ring-blue-200">
                                            <option value="1" <?= ($row['request_status'] == 1) ? 'selected' : '' ?>>Approved</option>
                                            <option value="0" <?= ($row['request_status'] == 0) ? 'selected' : '' ?>>Pending</option>
                                        </select>
                                    </div>
                                    <div class="flex justify-end space-x-2">
                                        <button type="button" onclick="closeModal('modal-<?= $row['id'] ?>')" class="px-4 py-2 rounded border">Cancel</button>
                                        <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">Update</button>
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
            </script>
        </div>
    </div>
</body>

</html>
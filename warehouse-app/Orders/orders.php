<?php

include '../../include/navbar.php';
$admin_id = isset($user_id) ? $user_id : 65;

// orders.php
include '../../include/connect-db.php'; // database connection

// Get all status options for the dropdown
$statusOptions = ['Pending', 'Approved', 'Shipped', 'Delivered', 'Cancelled'];

// Handle status update from modal (support AJAX)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['updateOrderStatus'])) {
    $orderId = $_POST['order_id'] ?? null;
    $newStatus = $_POST['order_status'] ?? null;

    if ($orderId && in_array($newStatus, $statusOptions)) {
        $stmtUp = $conn->prepare("UPDATE orders SET status = ?, updated_at=NOW() WHERE order_id = ?");
        $stmtUp->bind_param("si", $newStatus, $orderId);
        if ($stmtUp->execute()) {
            if (isset($_POST['ajax'])) {
                echo json_encode(['success' => true, 'new_status' => $newStatus]);
                exit;
            }
        } else {
            if (isset($_POST['ajax'])) {
                echo json_encode(['success' => false]);
                exit;
            }
        }
        header("Location: orders.php");
        exit;
    }
}

// Handle search and filters
$search = $_GET['search'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterDate = $_GET['date'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$perPage = 10; // Orders per page
$offset = ($page - 1) * $perPage;

// Build dynamic SQL for all orders
$sql = "SELECT 
            o.order_id,
            u.full_name AS shopowner_name,
            o.placed_at,
            o.updated_at,
            o.status, 
            total_order.total_quantity,
            total_order.total_amount
        FROM orders o
        JOIN users u ON o.shopowner_id = u.user_id
        JOIN (
            SELECT order_id, SUM(quantity) AS total_quantity, SUM(total_price) AS total_amount
            FROM order_items
            GROUP BY order_id
        ) AS total_order ON total_order.order_id = o.order_id
        WHERE 1=1";

$params = [];
$types = "";

// Search by product name (joins with order_items and products)
if ($search) {
    $sql .= " AND EXISTS (SELECT 1 FROM order_items oi JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = o.order_id AND p.name LIKE ?)";
    $params[] = "%$search%";
    $types .= "s";
}

// Filter by status
if ($filterStatus && in_array($filterStatus, $statusOptions)) {
    $sql .= " AND o.status = ?";
    $params[] = $filterStatus;
    $types .= "s";
}

// Filter by date
if ($filterDate) {
    switch ($filterDate) {
        case 'today':
            $sql .= " AND DATE(o.placed_at) = CURDATE()";
            break;
        case 'this_week':
            $sql .= " AND YEARWEEK(o.placed_at, 1) = YEARWEEK(CURDATE(), 1)";
            break;
        case 'this_month':
            $sql .= " AND MONTH(o.placed_at) = MONTH(CURDATE()) AND YEAR(o.placed_at) = YEAR(CURDATE())";
            break;
    }
}

$sql .= " ORDER BY o.placed_at DESC LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$types .= "ii";

// Prepare and execute
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get all orders as array
$ordersArr = [];
while ($row = $result->fetch_assoc()) {
    $ordersArr[] = $row;
}

// Total orders for pagination
$totalSql = "SELECT COUNT(*) as total FROM orders";
$totalRes = $conn->query($totalSql);
$totalOrders = $totalRes->fetch_assoc()['total'] ?? 0;
$totalPages = ceil($totalOrders / $perPage);

// Get all products per order for detail modal (for current page orders only to optimize)
$orderProducts = [];
if (count($ordersArr) > 0) {
    $orderIn = implode(',', array_map('intval', array_column($ordersArr, 'order_id')));
    $prodQ = "
        SELECT 
            oi.order_id,
            oi.product_id,
            p.name,
            oi.quantity,
            oi.unit_price,
            oi.total_price
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        WHERE oi.order_id IN ($orderIn)
        ORDER BY oi.order_id, oi.product_id
    ";
    $prodRes = $conn->query($prodQ);
    while ($row = $prodRes->fetch_assoc()) {
        $orderProducts[$row['order_id']][] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>All Orders - Stock Integrated</title>
    <link rel="icon" type="image/x-icon" href="../Logo/LogoBG.png">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .order-row {
            transition: all 0.3s ease;
        }

        .order-row:hover {
            background-color: #f9fafb;
            transform: translateY(-1px);
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

        .status-pending {
            color: #d97706;
        }

        .status-approved {
            color: #059669;
        }

        .status-shipped {
            color: #2563eb;
        }

        .status-delivered {
            color: #6b7280;
        }

        .status-cancelled {
            color: #dc2626;
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body class="bg-gray-100">

    <section class="home-section p-0">
        <div class="flex justify-between items-center p-4">
            <h1 class="text-2xl font-bold">All Orders</h1>
            <div class="flex space-x-2 no-print">
                <button onclick="window.print()"
                    class="bg-gray-500 text-white px-3 py-1 rounded text-sm hover:bg-gray-600">
                    <i class='bx bx-printer'></i> Print
                </button>
                <button class="bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600">
                    <i class='bx bx-plus'></i> New Order
                </button>
            </div>
        </div>

        <div class="container mx-auto px-4">

            <!-- Search & Filters -->
            <div class="bg-white shadow-lg rounded-lg p-6 mb-6 filter-container">
                <form method="GET" id="filterForm" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class='bx bx-search mr-1'></i>Search Orders
                        </label>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                            placeholder="Search by product name..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 search-field">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class='bx bx-flag mr-1'></i>Status Filter
                        </label>
                        <select name="status"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 search-field">
                            <option value="">All Status</option>
                            <?php foreach ($statusOptions as $opt): ?>
                                <option value="<?= $opt ?>" <?= ($filterStatus == $opt) ? 'selected' : '' ?>><?= $opt ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class='bx bx-calendar mr-1'></i>Date Filter
                        </label>
                        <select name="date"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 search-field">
                            <option value="">All Dates</option>
                            <option value="today" <?= ($filterDate == 'today') ? 'selected' : '' ?>>Today</option>
                            <option value="this_week" <?= ($filterDate == 'this_week') ? 'selected' : '' ?>>This Week
                            </option>
                            <option value="this_month" <?= ($filterDate == 'this_month') ? 'selected' : '' ?>>This Month
                            </option>
                        </select>
                    </div>
                </form>
            </div>

            <!-- Results Summary -->
            <div class="bg-white shadow-md rounded-lg p-4 mb-6">
                <div class="flex justify-between items-center">
                    <div class="text-lg font-semibold text-gray-700">
                        Showing page <?= $page ?> of <?= $totalPages ?> (<?= $totalOrders ?> total orders)
                    </div>
                    <div class="text-sm text-gray-500">
                        <?= count($ordersArr) ?> orders on this page
                    </div>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <i class='bx bx-hash mr-1'></i>Order ID
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <i class='bx bx-calendar mr-1'></i>Placed
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <i class='bx bx-time mr-1'></i>Updated
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <i class='bx bx-package mr-1'></i>Products
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <i class='bx bx-calculator mr-1'></i>Quantity
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <i class='bx bx-dollar mr-1'></i>Total
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <i class='bx bx-flag mr-1'></i>Status
                                </th>
                                <th
                                    class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider no-print">
                                    <i class='bx bx-cog'></i>Action
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($ordersArr)): ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                        <i class='bx bx-shopping-bag text-6xl mb-4 text-gray-300'></i>
                                        <div class="text-lg">No orders found</div>
                                        <div class="text-sm">Try adjusting your search criteria</div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($ordersArr as $order): ?>
                                    <tr class="order-row">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">#<?= $order['order_id'] ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                <?= date('d M, Y', strtotime($order['placed_at'])) ?></div>
                                            <div class="text-xs text-gray-500">
                                                <?= date('H:i', strtotime($order['placed_at'])) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                <?= date('d M, Y', strtotime($order['updated_at'])) ?></div>
                                            <div class="text-xs text-gray-500">
                                                <?= date('H:i', strtotime($order['updated_at'])) ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm">
                                                <?php if (isset($orderProducts[$order['order_id']])): ?>
                                                    <?php
                                                    $items = $orderProducts[$order['order_id']];
                                                    $displayCount = min(3, count($items));
                                                    for ($i = 0; $i < $displayCount; $i++): ?>
                                                        <div class="mb-1"><?= htmlspecialchars($items[$i]['name']) ?></div>
                                                    <?php endfor; ?>
                                                    <?php if (count($items) > 3): ?>
                                                        <div class="text-xs text-blue-600 cursor-pointer"
                                                            onclick="openOrderModal(<?= $order['order_id'] ?>)">
                                                            +<?= count($items) - 3 ?> more items
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm">
                                                <?php if (isset($orderProducts[$order['order_id']])): ?>
                                                    <?php
                                                    $items = $orderProducts[$order['order_id']];
                                                    $displayCount = min(3, count($items));
                                                    for ($i = 0; $i < $displayCount; $i++): ?>
                                                        <div class="mb-1 text-center"><?= $items[$i]['quantity'] ?></div>
                                                    <?php endfor; ?>
                                                    <?php if (count($items) > 3): ?>
                                                        <div class="text-xs text-gray-400">...</div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                $<?= number_format($order['total_amount'], 2) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $statusClasses = [
                                                'Pending' => 'bg-yellow-100 text-yellow-800 border border-yellow-200',
                                                'Approved' => 'bg-green-100 text-green-800 border border-green-200',
                                                'Shipped' => 'bg-blue-100 text-blue-800 border border-blue-200',
                                                'Delivered' => 'bg-gray-100 text-gray-800 border border-gray-200',
                                                'Cancelled' => 'bg-red-100 text-red-800 border border-red-200'
                                            ];
                                            $statusIcons = [
                                                'Pending' => 'bx-time-five',
                                                'Approved' => 'bx-check-circle',
                                                'Shipped' => 'bx-package',
                                                'Delivered' => 'bx-check-double',
                                                'Cancelled' => 'bx-x-circle'
                                            ];
                                            ?>
                                            <span
                                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold <?= $statusClasses[$order['status']] ?>">
                                                <i
                                                    class='<?= $statusIcons[$order['status']] ?> mr-1'></i><?= $order['status'] ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center no-print">
                                            <button onclick="openOrderModal(<?= $order['order_id'] ?>)"
                                                class="text-blue-600 hover:text-blue-900 font-medium">
                                                <i class='bx bx-show mr-1'></i>Details
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div class="bg-white shadow-md rounded-lg p-4 mt-6 flex justify-center items-center space-x-2">
                <?php if ($page > 1): ?>
                    <a href="?page=1&search=<?= urlencode($search) ?>&status=<?= urlencode($filterStatus) ?>&date=<?= urlencode($filterDate) ?>"
                        class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg transition-colors">
                        <i class='bx bx-chevrons-left'></i>
                    </a>
                    <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filterStatus) ?>&date=<?= urlencode($filterDate) ?>"
                        class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg transition-colors">
                        <i class='bx bx-chevron-left'></i>
                    </a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filterStatus) ?>&date=<?= urlencode($filterDate) ?>"
                        class="px-4 py-2 rounded-lg transition-colors <?= ($page == $i) ? 'bg-blue-500 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filterStatus) ?>&date=<?= urlencode($filterDate) ?>"
                        class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg transition-colors">
                        <i class='bx bx-chevron-right'></i>
                    </a>
                    <a href="?page=<?= $totalPages ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filterStatus) ?>&date=<?= urlencode($filterDate) ?>"
                        class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg transition-colors">
                        <i class='bx bx-chevrons-right'></i>
                    </a>
                <?php endif; ?>
            </div>

        </div>
    </section>

    <!-- Order Detail Modals -->
    <?php foreach ($ordersArr as $order): ?>
        <div id="order-modal-<?= $order['order_id'] ?>"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50 modal">
            <div class="bg-white w-full max-w-4xl rounded-lg shadow-lg m-4 modal-content">
                <div class="flex justify-between items-center p-6 border-b">
                    <h2 class="text-xl font-bold">Order #<?= $order['order_id'] ?> Details</h2>
                    <button class="text-gray-400 hover:text-gray-600 text-2xl"
                        onclick="closeOrderModal(<?= $order['order_id'] ?>)">
                        <i class='bx bx-x'></i>
                    </button>
                </div>

                <div class="p-6">
                    <!-- Order Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <div class="space-y-3">
                                <div class="flex items-center">
                                    <i class='bx bx-calendar mr-2 text-gray-400'></i>
                                    <strong>Placed At:</strong>
                                    <span class="ml-2"><?= date('d M, Y H:i', strtotime($order['placed_at'])) ?></span>
                                </div>
                                <div class="flex items-center">
                                    <i class='bx bx-time mr-2 text-gray-400'></i>
                                    <strong>Updated At:</strong>
                                    <span class="ml-2"><?= date('d M, Y H:i', strtotime($order['updated_at'])) ?></span>
                                </div>
                                <div class="flex items-center">
                                    <i class='bx bx-user mr-2 text-gray-400'></i>
                                    <strong>Shop Owner:</strong>
                                    <span class="ml-2"><?= htmlspecialchars($order['shopowner_name']) ?></span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center space-x-3">
                                <i class='bx bx-flag mr-2 text-gray-400'></i>
                                <strong>Status:</strong>
                                <select id="status-select-<?= $order['order_id'] ?>"
                                    class="border border-gray-300 rounded-md py-1 px-3 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <?php foreach ($statusOptions as $opt): ?>
                                        <option value="<?= $opt ?>" <?= ($order['status'] == $opt) ? 'selected' : '' ?>>
                                            <?= $opt ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button onclick="updateStatus(<?= $order['order_id'] ?>)"
                                    class="bg-blue-500 text-white px-3 py-1 rounded-md hover:bg-blue-600 transition-colors">
                                    <i class='bx bx-save mr-1'></i>Save
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Products Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full border border-gray-200 rounded-lg overflow-hidden">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Quantity
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Unit Price
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php
                                $orderTotal = 0;
                                if (isset($orderProducts[$order['order_id']])):
                                    foreach ($orderProducts[$order['order_id']] as $item):
                                        $orderTotal += $item['total_price'];
                                ?>
                                        <tr>
                                            <td class="px-4 py-3">
                                                <div class="font-medium text-gray-900"><?= htmlspecialchars($item['name']) ?></div>
                                            </td>
                                            <td class="px-4 py-3 text-center"><?= $item['quantity'] ?></td>
                                            <td class="px-4 py-3 text-right">$<?= number_format($item['unit_price'], 2) ?></td>
                                            <td class="px-4 py-3 text-right font-medium">
                                                $<?= number_format($item['total_price'], 2) ?></td>
                                        </tr>
                                <?php endforeach;
                                endif; ?>
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td class="px-4 py-3 font-bold text-gray-900" colspan="3">Total Amount</td>
                                    <td class="px-4 py-3 text-right font-bold text-lg text-gray-900">
                                        $<?= number_format($order['total_amount'], 2) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="flex justify-end p-6 border-t space-x-3">
                    <button type="button"
                        class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition-colors"
                        onclick="closeOrderModal(<?= $order['order_id'] ?>)">
                        Close
                    </button>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script>
        // Auto-submit filter on change
        document.querySelectorAll('select[name="status"], select[name="date"]').forEach(function(el) {
            el.addEventListener('change', function() {
                this.form.submit();
            });
        });
        document.querySelector('input[name="search"]').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });

        function openOrderModal(orderId) {
            document.getElementById('order-modal-' + orderId).classList.remove('hidden');
        }

        function closeOrderModal(orderId) {
            document.getElementById('order-modal-' + orderId).classList.add('hidden');
        }

        function updateStatus(orderId) {
            const select = document.getElementById('status-select-' + orderId);
            const newStatus = select.value;

            fetch('orders.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `updateOrderStatus=1&order_id=${orderId}&order_status=${newStatus}&ajax=1`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Status updated to ' + data.new_status);
                        location.reload();
                    } else {
                        alert('Failed to update status');
                    }
                })
                .catch(error => console.error('Error:', error));
        }
    </script>
</body>

</html>